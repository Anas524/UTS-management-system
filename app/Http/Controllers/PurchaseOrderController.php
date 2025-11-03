<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\InvoiceParser\InvoiceParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class PurchaseOrderController extends Controller
{
    public function index(Request $r)
    {
        $m = (int) $r->query('m', 0);   // 0 = all months, 1..12 specific month

        $q = PurchaseOrder::query()
            ->with(['rows' => function ($q) {
                // only what we need
                $q->select('id', 'po_sheet_id', 'qty', 'price_aed');
            }])
            ->where('user_id', Auth::id());

        if ($m >= 1 && $m <= 12) {
            $q->whereMonth('po_date', $m);
        }

        $list = $q->orderByRaw('po_date IS NULL')
            ->orderBy('po_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Totals for the filtered set (compute from rows: price_aed * qty)
        $subtotalIDR = 0;
        $taxIDR = 0;

        foreach ($list as $po) {
            $poSubtotal = 0;
            foreach ($po->rows as $r) {
                $unit = (int) ($r->price_aed ?? 0);   // IDR integer
                $qty  = (float) ($r->qty ?? 0);
                $poSubtotal += (int) round($unit * $qty);
            }
            $subtotalIDR += $poSubtotal;

            $rate = (float) ($po->ppn_rate ?? 0);
            $kind = strtolower($po->tax_kind ?? 'ppn');
            $tax  = ($kind === 'none') ? 0 : (int) round($poSubtotal * $rate / 100);

            // If PPH should be withholding, subtract here instead of adding
            $taxIDR += $tax;
        }

        $totalIDR = $subtotalIDR + $taxIDR;

        // Keep old variable names if your Blade expects them
        $subtotalFils = $subtotalIDR;
        $taxFils      = $taxIDR;
        $totalFils    = $totalIDR;

        $months = [
            0  => 'All months',
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4  => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8  => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return view('po.index', compact('list', 'subtotalFils', 'taxFils', 'totalFils', 'months', 'm'));
    }

    public function create()
    {
        return view('po.create'); // blank form with dynamic rows
    }

    public function store(Request $r)
    {
        $this->normalizeTax($r);

        // top-level validation
        $data = $r->validate([
            'po_number'     => 'nullable|string|max:255',
            'po_date'       => 'nullable|date_format:Y-m-d',
            'address'       => 'nullable|string|max:1000',
            'ppn_rate'      => 'nullable|numeric|min:0|max:100',
            'tax_kind'      => 'required|in:ppn,pph,none',
            'status'        => 'nullable|in:open,closed,awaiting_response,transferred',

            // Supplier info (left box)
            'sup_company'         => 'nullable|string|max:255',
            'sup_address'         => 'nullable|string|max:1000',
            'sup_phone'           => 'nullable|string|max:255',
            'sup_email'           => 'nullable|email|max:255',
            'sup_npwp'            => 'nullable|string|max:255',

            'ship_to_address'     => 'nullable|string|max:1000',
            'ship_to_phone'       => 'nullable|string|max:50',

            'payment_terms'       => 'nullable|string|max:1000',
            'delivery_time'       => 'nullable|string|max:255',
            'delivery_terms'      => 'nullable|string|max:255',

            'ship_to_recipient' => 'nullable|string|max:255',
            'conditions_terms'  => 'nullable|string|max:5000',

            'rows'                          => 'array',
            'rows.*.sku'                    => 'nullable|string|max:255',
            'rows.*.brand'                  => 'nullable|string|max:255',
            'rows.*.description'            => 'nullable|string|max:1000',
            'rows.*.price_aed'              => 'nullable|string',   // "IDR 12.34" etc — we’ll normalize
            'rows.*.qty'                    => 'nullable|numeric',
            'rows.*.unit'                   => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'user_id'      => Auth::id(),
                'po_number'    => $data['po_number'] ?? null,
                'po_date'      => $data['po_date'] ?? null,
                'address'      => $data['address'] ?? null,
                'ppn_rate'     => $data['ppn_rate'] ?? 0,
                'tax_kind'     => $data['tax_kind'] ?? 'ppn',
                'status'       => $data['status'] ?? 'open',

                // Supplier info
                'sup_company'         => $data['sup_company'] ?? null,
                'sup_address'         => $data['sup_address'] ?? null,
                'sup_phone'           => $data['sup_phone'] ?? null,
                'sup_email'           => $data['sup_email'] ?? null,
                'sup_npwp'     => $data['sup_npwp'] ?? null,

                // Ship To
                'ship_to_address'     => $data['ship_to_address'] ?? null,
                'ship_to_phone'       => $data['ship_to_phone'] ?? null,

                // Payment / Delivery
                'payment_terms'       => $data['payment_terms'] ?? null,
                'delivery_time'       => $data['delivery_time'] ?? null,
                'delivery_terms'      => $data['delivery_terms'] ?? null,

                'ship_to_recipient' => $data['ship_to_recipient'] ?? null,
                'conditions_terms'  => $data['conditions_terms']  ?? null,
            ]);

            $rows = $data['rows'] ?? [];
            $pos  = 1;

            foreach ($rows as $row) {
                // skip empty lines (no description & no price & no qty)
                $desc = trim($row['description'] ?? '');
                $qtyRaw  = isset($row['qty']) ? trim((string)$row['qty']) : '';
                $qty     = ($qtyRaw === '') ? null : (float) $qtyRaw;

                $idr = self::parseIDR($row['price_aed'] ?? null);

                if ($desc === '' && is_null($idr) && is_null($qty)) continue;

                PurchaseOrderRow::create([
                    'po_sheet_id' => $po->id,
                    'no'          => $pos++,
                    'sku'         => $row['sku'] ?? null,
                    'brand'       => $row['brand'] ?? null,
                    'description' => $desc ?: null,
                    'price_aed'   => $idr,
                    'qty'         => $qty,
                    'unit'        => self::cleanUnit($row['unit'] ?? null),
                ]);
            }

            return redirect()->route('po.show', $po)->with('status', 'PO saved.');
        });
    }

    /** "1.234.567" / "IDR 1,234,567" -> 1234567 (rupiah int). Returns null if empty. */
    private static function parseIDR(?string $s): ?int
    {
        if ($s === null) return null;
        $s = trim($s);
        if ($s === '') return null;
        // keep digits only
        $n = preg_replace('/\D+/', '', $s);
        return ($n === '') ? null : (int) $n;
    }

    /** default unit: 'kg'; also ignore literal "unit" */
    private static function cleanUnit(?string $u): ?string
    {
        $v = strtolower(trim((string)$u));
        if ($v === '' || $v === 'unit') return 'kg';
        return $u;
    }

    public function show(PurchaseOrder $po)
    {
        $this->authorize('view', $po);
        $po->load('rows')->loadCount('attachments');
        return view('po.show', compact('po'));
    }

    public function destroy(PurchaseOrder $po)
    {
        $this->authorize('delete', $po);           // uses your policy; or remove if not using policies
        $po->rows()->delete();                     // cascade rows
        $po->delete();
        return redirect()->route('po.index')->with('status', 'PO deleted.');
    }

    public function update(Request $r, PurchaseOrder $po)
    {
        $this->authorize('update', $po);

        // Validate only header first (always safe to run)
        $r->validate([
            'po_number'   => 'nullable|string|max:190',
            'po_date'     => 'nullable|date_format:Y-m-d',
            'address'     => 'nullable|string|max:500',
            'ppn_rate'    => 'nullable|numeric|min:0|max:100',
            'tax_kind' => 'required|in:ppn,pph,none',
            'status'      => 'nullable|in:open,closed,awaiting_response,transferred',

            // Supplier info
            'sup_company'         => 'nullable|string|max:255',
            'sup_address'         => 'nullable|string|max:1000',
            'sup_phone'           => 'nullable|string|max:255',
            'sup_email'           => 'nullable|email|max:255',
            'sup_npwp'            => 'nullable|string|max:255',

            // Ship To inputs
            'ship_to_address'     => 'nullable|string|max:1000',
            'ship_to_phone'       => 'nullable|string|max:50',

            // Payment / Delivery
            'payment_terms'       => 'nullable|string|max:1000',
            'delivery_time'       => 'nullable|string|max:255',
            'delivery_terms'      => 'nullable|string|max:255',

            'ship_to_recipient' => 'nullable|string|max:255',
            'conditions_terms'  => 'nullable|string|max:5000',
        ]);

        $this->normalizeTax($r, $po);

        $po->fill($r->only([
            'po_number',
            'po_date',
            'address',
            'ppn_rate',
            'tax_kind',
            'status',

            'sup_company',
            'sup_address',
            'sup_phone',
            'sup_email',
            'sup_npwp',

            'ship_to_address',
            'ship_to_phone',

            'payment_terms',
            'delivery_time',
            'delivery_terms',

            'ship_to_recipient',
            'conditions_terms',
        ]));
        $po->save();

        // If there are no rows in the payload, stop here (don’t touch existing rows)
        if (!$r->has('rows')) {
            if ($r->expectsJson() || $r->ajax()) {
                return response()->json(['ok' => true]);
            }
            return back()->with('status', 'PO updated.');
        }

        // When rows[] are present, validate the rows too (bulk mode)
        $r->validate([
            'rows'               => 'array',
            'rows.*.id'          => 'nullable|integer',
            'rows.*.sku'         => 'nullable|string|max:190',
            'rows.*.brand'       => 'nullable|string|max:190',
            'rows.*.description' => 'nullable|string|max:500',
            'rows.*.price_aed'   => 'nullable|string',  // we’ll normalize below
            'rows.*.qty'         => 'nullable|numeric',
            'rows.*.unit'        => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($r, $po) {
            $keepIds = [];

            foreach ((array)$r->input('rows', []) as $idx => $row) {
                $hasAny =
                    trim((string)($row['description'] ?? '')) !== '' ||
                    trim((string)($row['sku'] ?? ''))         !== '' ||
                    trim((string)($row['brand'] ?? ''))       !== '' ||
                    trim((string)($row['qty'] ?? ''))         !== '';

                if (!$hasAny) {
                    continue;
                }

                $aed = null;
                if (isset($row['price_aed']) && $row['price_aed'] !== '') {
                    $aed = self::parseIDR($row['price_aed']); // IDR integer
                }

                $qtyRaw = isset($row['qty']) ? trim((string)$row['qty']) : '';
                $qty    = ($qtyRaw === '') ? null : (float) $qtyRaw;

                $unit = $row['unit'] ?? null;
                $u    = strtolower(trim((string)$unit));
                if ($u === 'unit') $unit = 'kg'; // default

                $data = [
                    'no'          => $idx + 1,
                    'sku'         => $row['sku'] ?? null,
                    'brand'       => $row['brand'] ?? null,
                    'description' => $row['description'] ?? null,
                    'price_aed'   => $aed,
                    'qty'         => $qty,
                    'unit'        => $unit,
                ];

                if (!empty($row['id'])) {
                    $po->rows()->whereKey($row['id'])->update($data);
                    $keepIds[] = (int)$row['id'];
                } else {
                    $new = $po->rows()->create($data);
                    $keepIds[] = $new->id;
                }
            }

            // In bulk mode we reflect exactly what client sent: remove the rest
            $po->rows()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('po.show', $po)->with('status', 'PO updated.');
    }

    public function addRow(Request $r, PurchaseOrder $po)
    {
        $this->authorize('update', $po);

        $row = $po->rows()->create([
            'sku'         => $r->input('sku', ''),
            'description' => $r->input('description', 'New item'),
            'price_aed'   => $r->filled('price_aed') ? self::parseIDR($r->price_aed) : null,
            'qty'         => (float) $r->input('qty', 1),
            'unit'        => $r->input('unit', 'kg'),
        ]);

        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'ok'  => true,
                'row' => [
                    'id'          => $row->id,
                    'sku'         => $row->sku,
                    'description' => $row->description,
                    'price_aed'   => $row->price_aed, // fils
                    'qty'         => $row->qty,
                    'unit'        => $row->unit,
                ],
            ]);
        }

        return redirect()->route('po.show', $po);
    }

    public function updateRow(Request $r, PurchaseOrder $po, PurchaseOrderRow $row)
    {
        $this->authorize('update', $po);
        $row = $po->rows()->whereKey($row->getKey())->firstOrFail();

        $data = $r->validate([
            'sku'         => 'nullable|string|max:255',
            'brand'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price_aed'   => 'nullable|string',
            'qty'         => 'nullable|numeric',
            'unit'        => 'nullable|string|max:50',
        ]);

        if ($r->has('price_aed')) {
            $data['price_aed'] = $r->filled('price_aed')
                ? self::parseIDR($r->input('price_aed'))
                : null;
        }

        if ($r->filled('unit')) {
            $u = strtolower(trim($r->input('unit')));
            if ($u === '' || $u === 'unit') $data['unit'] = null;
        }

        $row->update($data);

        if ($r->expectsJson() || $r->ajax()) {
            return response()->json(['ok' => true, 'row' => $row->fresh()]);
        }
        return redirect()->route('po.show', $po)->with('status', 'Row updated.');
    }

    public function deleteRow(Request $r, PurchaseOrder $po, PurchaseOrderRow $row)
    {
        $this->authorize('update', $po);
        $row = $po->rows()->whereKey($row->getKey())->firstOrFail();
        $row->delete();

        if ($r->expectsJson() || $r->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('po.show', $po)->with('status', 'Row removed.');
    }

    // Smart import: returns a "draft" PO payload -> front-end fills the form
    public function import(Request $r, \App\Services\InvoiceParser\InvoiceParser $parser)
    {
        $r->validate(['file' => 'required|file|max:20480']);

        try {
            $f   = $r->file('file');
            $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension() ?: 'pdf');

            // ensure dir exists
            $dir = storage_path('app/tmp_po_imports');
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            // short filename with the right extension
            $name = 'po_' . Str::random(12) . '.' . $ext;
            $abs  = $dir . DIRECTORY_SEPARATOR . $name;
            $f->move($dir, $name); // move() writes a proper file accessible to external binaries

            $draft = $parser->parse($abs);

            @unlink($abs);

            return response()->json($draft);
        } catch (\Throwable $e) {
            Log::error('PO import failed', ['err' => $e->getMessage()]);
            return response()->json(['message' => 'Import failed'], 500);
        }
    }

    public function bulkSave(Request $request, \App\Models\PurchaseOrder $po)
    {
        $this->authorize('update', $po);

        $data = $request->validate([
            'rows'                   => ['array'],
            'rows.*.id'              => ['nullable', 'integer', 'exists:purchase_order_rows,id'],
            'rows.*.sku'             => ['nullable', 'string'],
            'rows.*.description'     => ['nullable', 'string'],
            'rows.*.price_aed'       => ['nullable', 'string'],
            'rows.*.qty'             => ['nullable', 'numeric'],
            'rows.*.unit'            => ['nullable', 'string'],
        ]);

        $rows = array_values($data['rows'] ?? []); // normalize indexes

        // Renumber here (this is where your snippet goes)
        foreach ($rows as $i => &$row) {
            $row['no']  = $i + 1;
            $row['qty'] = (float) ($row['qty'] ?? 0);

            $aed = trim((string)($row['price_aed'] ?? ''));
            $row['price_aed_idr'] = ($aed === '') ? null : self::parseIDR($aed);
        }
        unset($row); // break the ref

        DB::transaction(function () use ($po, $rows) {
            // Upsert rows and collect IDs we keep
            $keepIds = [];

            foreach ($rows as $row) {
                $payload = [
                    'po_sheet_id'  => $po->id,
                    'no'           => $row['no'],
                    'sku'          => $row['sku'] ?? null,
                    'description'  => $row['description'] ?? null,
                    'price_aed'    => $row['price_aed_idr'],
                    'qty'          => $row['qty'] ?? 0,
                    'unit'         => $row['unit'] ?? null,
                ];

                if (!empty($row['id'])) {
                    // update existing
                    $model = $po->rows()->whereKey($row['id'])->firstOrFail();
                    $model->fill($payload)->save();
                    $keepIds[] = $model->id;
                } else {
                    // create new
                    $model = $po->rows()->create($payload);
                    $keepIds[] = $model->id;
                }
            }

            $po->rows()->whereNotIn('id', $keepIds)->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function exportPdf(PurchaseOrder $po)
    {
        $this->authorize('view', $po);
        $po->load('rows');

        // Totals
        $rows = $po->rows ?? collect();
        $subtotalIDR = $rows->sum(function ($r) {
            $price = (int) ($r->price_aed ?? 0); // IDR int
            $qty   = (float) ($r->qty ?? 0);
            return (int) round($price * $qty);
        });
        $rate = is_null($po->ppn_rate) ? 0 : (float)$po->ppn_rate;
        $kind = strtolower($po->tax_kind ?? 'ppn');

        $taxIDR   = ($kind === 'none') ? 0 : (int) round($subtotalIDR * $rate / 100);
        $totalIDR = $subtotalIDR + $taxIDR; // flip if PPH is withholding

        $fmtIDR = fn(int $n) => 'IDR ' . number_format($n, 0, ',', '.');

        // amount in words (Indonesian - rupiah)
        $amountWords = (function (int $n): string {
            if (!class_exists(\NumberFormatter::class)) {
                return 'IDR ' . number_format($n, 0, ',', '.');
            }
            $fmt = new \NumberFormatter('id', \NumberFormatter::SPELLOUT);
            $w = $fmt->format($n);
            return $w ? ucfirst($w) . ' rupiah' : ('IDR ' . number_format($n, 0, ',', '.'));
        })($totalIDR);

        // --- Fixed "ORDER BY" info (no DB field needed) ---
        $orderBy = [
            'company' => 'PT. UNIVERSAL TRADE SERVICES',
            'npwp'    => '1000.0000.0070.1243',
            'lines'   => [
                'Cikini Building, JL. Cikini Raya No. 9, RT 016/ RW 001, Cikini, Menteng',
                'Kota Adm. Jakarta Pusat, DKI Jakarta',
            ],
        ];

        // "SHIP TO" — use your PO’s free-text address (or customize if you add columns later)
        $shipTo = [
            'title'   => 'Ship To',
            'lines' => array_filter([(string) $po->address]),
        ];

        // Preload background image as base64 (avoid filesystem reads inside Blade)
        $bgData = null;
        $bgPath = public_path('pdf/pdf-export.png');
        if (is_file($bgPath)) {
            // TIP: keep this image ~150–200 DPI A4 to avoid memory spikes
            $bgData = base64_encode(file_get_contents($bgPath));
        }

        // Dompdf options: allow local assets & HTML5 parser (less edge-case CSS errors)
        $options = [
            'isRemoteEnabled'       => true,
            'isHtml5ParserEnabled'  => true,
            'chroot'                => public_path(), // lock Dompdf to /public
            'dpi'                   => 96,            // keep moderate DPI
            'defaultFont'           => 'DejaVu Sans',
        ];

        // Tax label based on kind
        $rateTxt = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
        $taxLabel = match ($kind) {
            'ppn'  => "Pajak Pertambahan Nilai (PPN) {$rateTxt}%",
            'pph'  => "Pajak Penghasilan (PPH) {$rateTxt}%",
            default => '',
        };

        $pdf = Pdf::setOptions($options)
            ->loadView('po.pdf', [
                'po'          => $po,
                'rows'        => $rows,
                'subtotal'    => $fmtIDR($subtotalIDR),
                'ppn'         => $fmtIDR($taxIDR),
                'total'       => $fmtIDR($totalIDR),
                'amountWords' => $amountWords,
                'bgData'      => $bgData,
                'taxLabel'    => $taxLabel,
                'orderBy'     => $orderBy,
                'shipTo'      => $shipTo,
            ])
            ->setPaper('a4', 'portrait');

        if (request('debug') === '1') {
            return view('po.pdf', [
                'po'          => $po,
                'rows'        => $rows,
                'subtotal'    => $fmtIDR($subtotalIDR),
                'ppn'         => $fmtIDR($taxIDR),
                'total'       => $fmtIDR($totalIDR),
                'amountWords' => $amountWords,
                'bgData'      => $bgData,
                'taxLabel'    => $taxLabel,
            ]);
        }

        $num = $po->po_number ?: ('PO-' . $po->id);
        $filename = Str::of($num)->replace(['/', '\\', ' '], '_') . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    public function find(Request $r)
    {
        $q    = trim((string) $r->query('q', ''));
        $type = $r->query('type', 'supplier'); // 'supplier' | 'number'

        $builder = PurchaseOrder::query();

        if (Auth::check()) {
            $builder->where('user_id', Auth::id());
        }

        if ($type === 'number') {
            if ($q !== '') $builder->where('po_number', 'like', "%{$q}%");
        } else {
            if ($q !== '') {
                $builder->where('sup_company', 'like', "%{$q}%");
            }
        }

        $items = $builder
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get([
                'id',
                'po_number',
                'po_date',
                'sup_company',
                'sup_address',
                'sup_phone',
                'sup_email',
                'sup_npwp',
            ]);

        return response()->json(
            $items->map(fn($po) => [
                'id' => $po->id,
                'po_number' => (string)($po->po_number ?? ''),
                'po_date'   => optional($po->po_date)->format('Y-m-d'),
                'sup_company' => (string)($po->sup_company ?? ''),
                'sup_address' => (string)($po->sup_address ?? ''),
                'sup_phone'   => (string)($po->sup_phone ?? ''),
                'sup_email'   => (string)($po->sup_email ?? ''),
                'sup_npwp'    => (string)($po->sup_npwp ?? ''),
            ])
        );
    }

    public function get(Request $r)
    {
        $id = (int) $r->query('id');

        $po = PurchaseOrder::query()
            ->with(['rows' => function ($q) {
                $q->select('id', 'po_sheet_id', 'sku', 'brand', 'description', 'qty', 'price_aed');
            }])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'po' => [
                'id'                 => $po->id,
                'po_number'          => (string) $po->po_number,
                'po_date'            => optional($po->po_date)->format('Y-m-d'),
                'ppn_rate'           => (float) ($po->ppn_rate ?? 0),
                'tax_kind'           => (string) ($po->tax_kind ?? 'ppn'),
                'status'             => (string) ($po->status ?? 'open'),
                'address'            => (string) ($po->address ?? ''),

                'sup_company'        => (string) ($po->sup_company ?? ''),
                'sup_address'        => (string) ($po->sup_address ?? ''),
                'sup_phone'          => (string) ($po->sup_phone ?? ''),
                'sup_email'          => (string) ($po->sup_email ?? ''),
                'sup_npwp'           => (string) ($po->sup_npwp ?? ''),

                'ship_to_recipient'  => (string) ($po->ship_to_recipient ?? ''),
                'ship_to_address'    => (string) ($po->ship_to_address ?? ''),
                'ship_to_phone'      => (string) ($po->ship_to_phone ?? ''),

                'payment_terms'      => (string) ($po->payment_terms ?? ''),
                'delivery_time'      => (string) ($po->delivery_time ?? ''),
                'delivery_terms'     => (string) ($po->delivery_terms ?? ''),
                'conditions_terms'   => (string) ($po->conditions_terms ?? ''),
            ],
            'rows' => $po->rows->map(fn($r) => [
                'sku'         => (string) ($r->sku ?? ''),
                'brand'       => (string) ($r->brand ?? ''),
                'description' => (string) ($r->description ?? ''),
                'qty'         => (float)  ($r->qty ?? 0),
                'price_aed'   => (int)    ($r->price_aed ?? 0), // cents
            ])->values(),
        ]);
    }

    private function normalizeTax(Request $r, ?PurchaseOrder $po = null): void
    {
        // kind: only ppn | pph | none
        $kind = strtolower((string)$r->input('tax_kind', 'ppn'));
        if (!in_array($kind, ['ppn', 'pph', 'none'], true)) {
            $kind = 'ppn';
        }

        // rate: 0 if none; else numeric, clamped 0..100
        $rate = ($kind === 'none') ? 0.0 : (float)$r->input('ppn_rate', 0);
        if ($rate < 0)   $rate = 0.0;
        if ($rate > 100) $rate = 100.0;

        // reflect back into request (so validation/old() stay in sync)
        $r->merge([
            'tax_kind' => $kind,
            'ppn_rate' => $rate,
        ]);

        // optionally enforce on an existing model too
        if ($po) {
            $po->tax_kind = $kind;
            $po->ppn_rate = $rate;
        }
    }
}
