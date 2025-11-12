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
use App\Support\Num;

class PurchaseOrderController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $u = Auth::user();
        $m = (int) $r->query('m', 0);   // 0 = all months, 1..12 = specific month

        $q = PurchaseOrder::query()
            ->with(['rows' => function ($q) {
                // include amount so we can sum exact integers like Expense rows
                $q->select('id', 'po_sheet_id', 'qty', 'price_aed', 'amount');
            }]);

        // Normal users: only their POs. Admins & consultants: see all.
        if (!$u->is_admin && ($u->role ?? null) !== 'consultant') {
            $q->where('user_id', $u->id);
        }

        if ($m >= 1 && $m <= 12) {
            $q->whereMonth('po_date', $m);
        }

        $list = $q->orderByRaw('po_date IS NULL')
            ->orderBy('po_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        // ---- Cards totals (IDR integers, mirror show/pdf: ABSOLUTE tax) ----
        $subtotalInt = 0;
        $taxInt      = 0;
        $totalInt    = 0;

        foreach ($list as $po) {
            // Subtotal — recompute every line
            $poSubtotalInt = (int) ($po->rows ?? collect())->sum(function ($r) {
                $unit = (float)($r->price_aed ?? 0);
                $qty  = (float)($r->qty ?? 0);
                return (int) round($unit * $qty, 0);
            });

            $subtotalInt += $poSubtotalInt;

            // Absolute tax in ppn_rate
            $kind   = strtolower($po->tax_kind ?? 'ppn');
            $absTax = ($kind === 'none') ? 0 : (int) round((float)($po->ppn_rate ?? 0));
            $taxInt += $absTax;

            $totalInt += ($kind === 'pph')
                ? max(0, $poSubtotalInt - $absTax)
                : $poSubtotalInt + $absTax;
        }

        // Pass raw ints; format in Blade
        $subtotalFils = $subtotalInt;
        $taxFils      = $taxInt;
        $totalFils    = $totalInt;

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
        $this->normalizeTax($r);

        // top-level validation
        $data = $r->validate([
            'po_number'     => 'nullable|string|max:255',
            'po_date'       => 'nullable|date_format:Y-m-d',
            'address'       => 'nullable|string|max:1000',
            'tax_kind'      => 'required|in:ppn,pph,none',
            'ppn_rate'      => 'nullable|numeric',
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

        $manual = Num::normalize($data['ppn_rate'] ?? null, 4) ?? '0.0000';

        return DB::transaction(function () use ($data, $manual) {
            $po = PurchaseOrder::create([
                'user_id'      => Auth::id(),
                'po_number'    => $data['po_number'] ?? null,
                'po_date'      => $data['po_date'] ?? null,
                'address'      => $data['address'] ?? null,
                'tax_kind'     => $data['tax_kind'] ?? 'ppn',
                'ppn_rate'  => (($data['tax_kind'] ?? 'ppn') === 'none') ? '0.0000' : $manual,
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

                $idr = Num::normalize($row['price_aed'] ?? null, 4); // returns "123.4500" or null

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

    /** Map a non-negative integer (rupiah) to Indonesian words, no "rupiah" suffix. */
    private static function idrWordsInt(int $n): string
    {
        $s = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        $spell = function (int $x) use (&$spell, $s): string {
            if ($x < 12) return $s[$x];
            if ($x < 20) return $spell($x - 10) . ' belas';
            if ($x < 100) {
                $t = intdiv($x, 10);
                $r = $x % 10;
                return $spell($t) . ' puluh' . ($r ? ' ' . $spell($r) : '');
            }
            if ($x < 200) return 'seratus' . ($x > 100 ? ' ' . $spell($x - 100) : '');
            if ($x < 1000) {
                $h = intdiv($x, 100);
                $r = $x % 100;
                return $spell($h) . ' ratus' . ($r ? ' ' . $spell($r) : '');
            }
            if ($x < 2000) return 'seribu' . ($x > 1000 ? ' ' . $spell($x - 1000) : '');
            if ($x < 1000000) {
                $k = intdiv($x, 1000);
                $r = $x % 1000;
                return $spell($k) . ' ribu' . ($r ? ' ' . $spell($r) : '');
            }
            if ($x < 1000000000) {
                $m = intdiv($x, 1000000);
                $r = $x % 1000000;
                return $spell($m) . ' juta' . ($r ? ' ' . $spell($r) : '');
            }
            if ($x < 1000000000000) {
                $b = intdiv($x, 1000000000);
                $r = $x % 1000000000;
                return $spell($b) . ' miliar' . ($r ? ' ' . $spell($r) : '');
            }
            $t = intdiv($x, 1000000000000);
            $r = $x % 1000000000000;
            return $spell($t) . ' triliun' . ($r ? ' ' . $spell($r) : '');
        };

        return $spell($n);
    }

    /** From a decimal string like "34120.6650" -> "Tiga puluh ... rupiah koma enam enam lima". */
    private static function idrWordsFull(string $decimal): string
    {
        $decimal = trim((string)$decimal);
        if ($decimal === '') return 'Nol rupiah';

        $neg = false;
        if ($decimal[0] === '-') {
            $neg = true;
            $decimal = substr($decimal, 1);
        }

        // Ensure dot as separator and only digits + dot
        $decimal = preg_replace('/[^0-9.]/', '', $decimal) ?? '0';

        $parts = explode('.', $decimal, 2);
        $intPart  = (int) ($parts[0] === '' ? 0 : $parts[0]);
        $fracPart = isset($parts[1]) ? rtrim($parts[1], '0') : '';

        $map = ['0' => 'nol', '1' => 'satu', '2' => 'dua', '3' => 'tiga', '4' => 'empat', '5' => 'lima', '6' => 'enam', '7' => 'tujuh', '8' => 'delapan', '9' => 'sembilan'];

        $base = self::idrWordsInt($intPart) . ' rupiah';
        if ($fracPart !== '') {
            $digits = implode(' ', array_map(fn($d) => $map[$d], str_split($fracPart)));
            $base .= ' koma ' . $digits;
        }

        $out = ($neg ? 'minus ' : '') . $base;
        return ucfirst($out);
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
            'tax_kind'    => 'required|in:ppn,pph,none',
            'ppn_rate'    => 'nullable|numeric',
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

        $manual = Num::normalize($r->input('ppn_rate'), 4) ?? '0.0000';

        $po->fill($r->only([
            'po_number',
            'po_date',
            'address',
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

        // force store absolute IDR tax here
        $po->ppn_rate = ($po->tax_kind === 'none') ? '0.0000' : $manual;

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
            'rows.*.price_aed'   => 'nullable|numeric',  // we’ll normalize below
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

                $aed = \App\Support\Num::normalize($row['price_aed'] ?? null, 4); // "522.2200" or null

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

        $nextNo = ((int) $po->rows()->max('no')) + 1;

        // normalize or force zero (DECIMAL(18,4))
        $price = $r->filled('price_aed')
            ? \App\Support\Num::normalize($r->input('price_aed'), 4)
            : '0.0000';

        // qty as float, default 1
        $qtyRaw = trim((string) $r->input('qty', '1'));
        $qty    = ($qtyRaw === '') ? 1.0 : (float) $qtyRaw;

        // unit: default to 'kg'; ignore literal "unit"
        $unit = $r->input('unit', 'kg');
        $u    = strtolower(trim((string)$unit));
        if ($u === '' || $u === 'unit') $unit = 'kg';

        $row = $po->rows()->create([
            'no'          => $nextNo,
            'sku'         => (string) $r->input('sku', ''),
            'brand'       => (string) $r->input('brand', ''),
            'description' => (string) $r->input('description', 'New item'),
            'price_aed'   => $price,           // ← never null
            'qty'         => $qty,
            'unit'        => $unit,
        ]);

        return response()->json([
            'ok'  => true,
            'row' => [
                'id'          => $row->id,
                'no'          => $row->no,          // send back for UI numbering
                'sku'         => (string) $row->sku,
                'brand'       => (string) $row->brand,
                'description' => (string) $row->description,
                'price_aed'   => (string) $row->price_aed,
                'qty'         => (float)  ($row->qty ?? 0),
                'unit'        => (string) ($row->unit ?? ''),
            ],
        ], 201);
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
            $data['price_aed'] = $r->filled('price_aed') ? Num::normalize($r->input('price_aed'), 4) : null;
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
            $row['price_aed_norm'] = ($aed === '') ? null : \App\Support\Num::normalize($aed, 4);
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
                    'price_aed'    => $row['price_aed_norm'],
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
        $this->authorize('download', $po);
        $this->authorize('view', $po);
        $po->load('rows');

        $rows = $po->rows ?? collect();

        // 1) Subtotal: sum the stored integer rupiah (already rounded per line)
        $subtotalInt = (int) $rows->sum(fn($r) => (int) ($r->amount ?? 0));

        // 2) Tax: treat ppn_rate as percent (%). If 'none' or <=0 → 0.
        $kind = strtolower($po->tax_kind ?? 'ppn');   // 'ppn' | 'pph' | 'none'
        $rate = (float) ($po->ppn_rate ?? 0);         // percent in UI (e.g. 9)
        $taxInt = ($kind === 'none' || $rate <= 0)
            ? 0
            : (int) round($subtotalInt * $rate / 100);

        // 3) Total: add tax (or subtract if you treat PPH as withholding)
        $totalInt = ($kind === 'pph')
            ? max(0, $subtotalInt - $taxInt)
            : $subtotalInt + $taxInt;

        // 4) Formatters
        $fmtIDR0 = fn(int $n) => 'IDR ' . number_format($n, 0, ',', '.');
        $rateLbl = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.'); // "9" or "1.5"
        $taxLabel = ($kind === 'none' || $rate <= 0) ? '' : strtoupper($kind) . " ({$rateLbl}%)";

        // 5) Amount in words
        $amountWords = self::idrWordsFull((string) $totalInt);

        // 6) Background (optional)
        $bgData = null;
        $bgPath = public_path('pdf/pdf-export.png');
        if (is_file($bgPath)) $bgData = base64_encode(file_get_contents($bgPath));

        $options = [
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
            'chroot'               => public_path(),
            'dpi'                  => 96,
            'defaultFont'          => 'DejaVu Sans',
        ];

        $payload = [
            'po'          => $po,
            'rows'        => $rows,
            'subtotal'    => $fmtIDR0($subtotalInt),
            'ppn'         => $fmtIDR0($taxInt),
            'total'       => $fmtIDR0($totalInt),
            'amountWords' => $amountWords,
            'bgData'      => $bgData,
            'taxLabel'    => $taxLabel,
        ];

        if (request('debug') === '1') return view('po.pdf', $payload);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::setOptions($options)
            ->loadView('po.pdf', $payload)
            ->setPaper('a4', 'portrait');

        $num = $po->po_number ?: ('PO-' . $po->id);
        $filename = \Illuminate\Support\Str::of($num)->replace(['/', '\\', ' '], '_') . '_' . now()->format('Ymd_His') . '.pdf';

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
                'ppn_rate'           => (string) ($po->ppn_rate ?? '0'),
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
                'price_aed' => (string) ($r->price_aed ?? '0'),
            ])->values(),
        ]);
    }

    private function normalizeTax(Request $r, ?PurchaseOrder $po = null): void
    {
        $kind = strtolower((string)$r->input('tax_kind', 'ppn'));
        if (!in_array($kind, ['ppn', 'pph', 'none'], true)) {
            $kind = 'ppn';
        }
        $r->merge(['tax_kind' => $kind]);

        if ($po) $po->tax_kind = $kind;
    }

    public function __construct()
    {
        // route param must match your routes: /po/{po}
        $this->authorizeResource(\App\Models\PurchaseOrder::class, 'po');
    }
}
