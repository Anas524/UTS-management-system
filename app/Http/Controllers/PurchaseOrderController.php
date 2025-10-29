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
                $q->select('id', 'po_sheet_id', 'qty', 'price_aed', 'amount');
            }])
            ->withSum('rows as subtotal_fils', 'amount')
            ->where('user_id', Auth::id());

        // filter by month across ALL years
        if ($m >= 1 && $m <= 12) {
            $q->whereMonth('po_date', $m);
        }

        $list = $q->orderByRaw('po_date IS NULL')
            ->orderBy('po_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Totals for the filtered set
        $subtotalFils = (int) ($list->sum('subtotal_fils') ?? 0);
        // VAT is per row’s subtotal × row’s ppn_rate equivalent (here use PO header ppn_rate)
        $taxFils = 0;
        foreach ($list as $po) {
            $rate = (float) ($po->ppn_rate ?? 0);
            $taxFils += (int) round(($po->subtotal_fils ?? 0) * $rate / 100);
        }
        $totalFils = $subtotalFils + $taxFils;

        // Month list (labels only, no year)
        $months = [
            0  => 'All months',
            1  => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5  => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9  => 'September',
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
        // top-level validation
        $data = $r->validate([
            'company_name'  => 'nullable|string|max:255',
            'po_number'     => 'nullable|string|max:255',
            'po_date'       => 'nullable|date_format:Y-m-d',
            'vendor'        => 'nullable|string|max:255',
            'npwp'          => 'nullable|string|max:255',
            'address'       => 'nullable|string|max:1000',
            'ppn_rate'      => 'nullable|numeric|min:0|max:100',
            'prepared_by'   => 'nullable|string|max:190',
            'tax_kind'      => 'nullable|in:VAT',
            'status'        => 'nullable|in:open,closed,awaiting_response,transferred',

            // Supplier info (left box)
            'sup_company'         => 'nullable|string|max:255',
            'sup_address'         => 'nullable|string|max:1000',
            'sup_phone'           => 'nullable|string|max:255',
            'sup_email'           => 'nullable|email|max:255',
            'sup_contact_person'  => 'nullable|string|max:255',
            'sup_contact_phone'   => 'nullable|string|max:255',
            'sup_contact_email'   => 'nullable|email|max:255',

            'currency'       => 'nullable|string|max:10',

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
                'company_name' => $data['company_name'] ?? null,
                'po_number'    => $data['po_number'] ?? null,
                'po_date'      => $data['po_date'] ?? null,
                'vendor'       => $data['vendor'] ?? null,
                'npwp'         => $data['npwp'] ?? null,
                'address'      => $data['address'] ?? null,
                'ppn_rate'     => $data['ppn_rate'] ?? 0,
                'prepared_by'  => $data['prepared_by'] ?? null,
                'tax_kind'     => $data['tax_kind'] ?? 'VAT',
                'status'       => $data['status'] ?? 'open',

                // Supplier info
                'sup_company'         => $data['sup_company'] ?? null,
                'sup_address'         => $data['sup_address'] ?? null,
                'sup_phone'           => $data['sup_phone'] ?? null,
                'sup_email'           => $data['sup_email'] ?? null,
                'sup_contact_person'  => $data['sup_contact_person'] ?? null,
                'sup_contact_phone'   => $data['sup_contact_phone'] ?? null,
                'sup_contact_email'   => $data['sup_contact_email'] ?? null,

                'currency'       => $data['currency']       ?? 'IDR',
            ]);

            $rows = $data['rows'] ?? [];
            $pos  = 1;

            foreach ($rows as $row) {
                // skip empty lines (no description & no price & no qty)
                $desc = trim($row['description'] ?? '');
                $qtyRaw  = isset($row['qty']) ? trim((string)$row['qty']) : '';
                $qty     = ($qtyRaw === '') ? null : (float) $qtyRaw;

                $aedFils = self::aedToFils($row['price_aed'] ?? null);

                if ($desc === '' && is_null($aedFils) && is_null($qty)) continue;

                PurchaseOrderRow::create([
                    'po_sheet_id' => $po->id,
                    'no'          => $pos++,
                    'sku'         => $row['sku'] ?? null,
                    'brand'       => $row['brand'] ?? null,
                    'description' => $desc ?: null,
                    'price_aed'   => $aedFils,
                    'qty'         => $qty,
                    'unit'        => self::cleanUnit($row['unit'] ?? null),
                ]);
            }

            return redirect()->route('po.show', $po)->with('status', 'PO saved.');
        });
    }

    /** "IDR 12.34" / "12,34" -> 1234 (fils). Returns null if empty. */
    private static function aedToFils(?string $s): ?int
    {
        if ($s === null) return null;
        $s = trim($s);
        if ($s === '') return null;
        $s = preg_replace('/[^0-9,.\-]/', '', $s);
        if (strpos($s, ',') !== false && strpos($s, '.') === false) {
            $s = str_replace(',', '.', $s);   // "12,34" -> "12.34"
        } else {
            $s = str_replace(',', '', $s);    // remove thousands commas
        }
        $f = (float)$s;
        return (int) round($f * 100);
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
            'prepared_by' => 'nullable|string|max:190',
            'po_number'   => 'nullable|string|max:190',
            'po_date'     => 'nullable|date_format:Y-m-d',
            'vendor'      => 'nullable|string|max:190',
            'npwp'        => 'nullable|string|max:190',
            'address'     => 'nullable|string|max:500',
            'ppn_rate'    => 'nullable|numeric|min:0|max:100',
            'currency'    => 'nullable|string|max:10',
            'tax_kind'    => 'nullable|in:VAT',
            'status'      => 'nullable|in:open,closed,awaiting_response,transferred',
            'sup_company'         => 'nullable|string|max:255',
            'sup_address'         => 'nullable|string|max:1000',
            'sup_phone'           => 'nullable|string|max:255',
            'sup_email'           => 'nullable|email|max:255',
            'sup_contact_person'  => 'nullable|string|max:255',
            'sup_contact_phone'   => 'nullable|string|max:255',
            'sup_contact_email'   => 'nullable|email|max:255',
        ]);

        $po->fill($r->only([
            'prepared_by',
            'po_number',
            'po_date',
            'vendor',
            'npwp',
            'address',
            'ppn_rate',
            'currency',
            'tax_kind',
            'status',

            // supplier fields
            'sup_company', 
            'sup_address',
            'sup_phone',
            'sup_email',
            'sup_contact_person',
            'sup_contact_phone',
            'sup_contact_email',
        ]))->save();

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
                if (!trim($row['description'] ?? '')) continue;

                $aed = null;
                if (isset($row['price_aed']) && $row['price_aed'] !== '') {
                    $aed = self::aedToFils($row['price_aed']);
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
            'price_aed'   => $r->filled('price_aed') ? self::aedToFils($r->price_aed) : null,
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
                ? self::aedToFils($r->input('price_aed'))
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
            if ($aed === '') {
                $row['price_aed_fils'] = null;
            } else {
                // reuse the same normalizer logic
                $row['price_aed_fils'] = self::aedToFils($aed);
            }
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
                    'price_aed'    => $row['price_aed_fils'],
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
        $rows     = $po->rows ?? collect();
        $subtotalFils = $rows->sum(function ($r) {
            $price = (int) ($r->price_aed ?? 0); // fils
            $qty   = (float) ($r->qty ?? 0);
            return (int) round($price * $qty);
        });
        $rate     = is_null($po->ppn_rate) ? 0 : (float)$po->ppn_rate;
        $taxFils    = (int) round($subtotalFils * $rate / 100);
        $totalFils  = $subtotalFils + $taxFils;

        $fmtIDR = fn(int $fils) => 'IDR ' . number_format($fils / 100, 2, '.', ',');

        // amount in words (Indonesian - rupiah)
        $terbilang = function (int $n): string {
            $n = (int) max(0, $n);
            $s = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
            $f = function ($x) use (&$f, $s): string {
                if ($x < 12) return $s[$x];
                if ($x < 20) return $f($x - 10) . ' belas';
                if ($x < 100) return $f(intval($x / 10)) . ' puluh' . ($x % 10 ? ' ' . $f($x % 10) : '');
                if ($x < 200) return 'seratus' . ($x - 100 ? ' ' . $f($x - 100) : '');
                if ($x < 1000) return $f(intval($x / 100)) . ' ratus' . ($x % 100 ? ' ' . $f($x % 100) : '');
                if ($x < 2000) return 'seribu' . ($x - 1000 ? ' ' . $f($x - 1000) : '');
                if ($x < 1000000) return $f(intval($x / 1000)) . ' ribu' . ($x % 1000 ? ' ' . $f($x % 1000) : '');
                if ($x < 1000000000) return $f(intval($x / 1000000)) . ' juta' . ($x % 1000000 ? ' ' . $f($x % 1000000) : '');
                if ($x < 1000000000000) return $f(intval($x / 1000000000)) . ' miliar' . ($x % 1000000000 ? ' ' . $f($x % 1000000000) : '');
                return $f(intval($x / 1000000000000)) . ' triliun' . ($x % 1000000000000 ? ' ' . $f($x % 1000000000000) : '');
            };
            return $f($n);
        };
        $amountWords = ucfirst($terbilang((int) floor($totalFils / 100))) . ' rupiah';

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
            'lines'   => array_filter([
                (string) $po->address,   // existing free-text address
            ]),
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

        $pdf = Pdf::setOptions($options)
            ->loadView('po.pdf', [
                'po'          => $po,
                'rows'        => $rows,
                'subtotal'    => $fmtIDR($subtotalFils),
                'ppn'         => $fmtIDR($taxFils),
                'total'       => $fmtIDR($totalFils),
                'amountWords' => $amountWords,
                'bgData'      => $bgData,
                'taxLabel'    => 'PPN / PPH ' . rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%',
                'orderBy'     => $orderBy,
                'shipTo'      => $shipTo,
            ])
            ->setPaper('a4', 'portrait');

        if (request('debug') === '1') {
            return view('po.pdf', [
                'po'          => $po,
                'rows'        => $rows,
                'subtotal'    => $fmtIDR($subtotalFils),
                'ppn'         => $fmtIDR($taxFils),
                'total'       => $fmtIDR($totalFils),
                'amountWords' => $amountWords,
                'bgData'      => $bgData,
                'taxLabel'    => 'PPN / PPH ' . rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%',
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

        // Keep user scope if you use per-user POs
        if (Auth::check()) {
            $builder->where('user_id', Auth::id());
        }

        if ($type === 'number') {
            if ($q !== '') {
                $builder->where('po_number', 'like', "%{$q}%");
            }
        } else {
            // Supplier search: match both prepared_by and sup_company (and contact person as fallback)
            if ($q !== '') {
                $builder->where(function ($w) use ($q) {
                    $w->where('prepared_by', 'like', "%{$q}%")
                        ->orWhere('sup_company', 'like', "%{$q}%")
                        ->orWhere('sup_contact_person', 'like', "%{$q}%");
                });
            }
        }

        // Always return a few recent items when q is empty
        $items = $builder
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'po_number', 'prepared_by', 'sup_company', 'po_date']);

        return response()->json(
            $items->map(fn($po) => [
                'id'          => $po->id,
                'po_number'   => (string) ($po->po_number ?? ''),
                'prepared_by' => (string) ($po->prepared_by ?: $po->sup_company ?: ''),
                'po_date'     => optional($po->po_date)->format('Y-m-d'),
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

        // If you have a policy:
        // $this->authorize('view', $po);

        return response()->json([
            'po' => [
                'id'                  => $po->id,
                'prepared_by'         => (string) $po->prepared_by,
                'po_number'           => (string) $po->po_number,
                'po_date'             => optional($po->po_date)->format('Y-m-d'),
                'npwp'                => (string) $po->npwp,
                'ppn_rate'            => (float) ($po->ppn_rate ?? 0),
                'address'             => (string) $po->address,
                'sup_company'         => (string) $po->sup_company,
                'sup_address'         => (string) $po->sup_address,
                'sup_phone'           => (string) $po->sup_phone,
                'sup_email'           => (string) $po->sup_email,
                'sup_contact_person'  => (string) $po->sup_contact_person,
                'sup_contact_phone'   => (string) $po->sup_contact_phone,
                'sup_contact_email'   => (string) $po->sup_contact_email,
            ],
            'rows' => $po->rows->map(fn($r) => [
                'sku'         => (string) $r->sku,
                'brand'       => (string) $r->brand,
                'description' => (string) $r->description,
                'qty'         => (float)  ($r->qty ?? 0),
                // Stored as cents/fils; frontend converts to whole IDR
                'price_aed'   => (int)    ($r->price_aed ?? 0),
            ])->values(),
        ]);
    }
}
