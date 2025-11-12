<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO {{ $po->po_number }}</title>
    <link rel="icon" href="{{ asset('images/UTS.png') }}">
    <style>
        @page {
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            font-size: 11px;
        }

        body {
            margin: 60mm 14mm 28mm 14mm;
        }

        /* header: title left, created right */
        .headbar {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 0 0 6px
        }

        .head-left,
        .head-right {
            display: table-cell;
            vertical-align: bottom
        }

        .head-left h1 {
            margin: 0;
            font-size: 20px;
            color: #0f3d56;
            font-weight: 800;
        }

        .head-right {
            text-align: right;
            color: #6b7280;
            white-space: nowrap
        }

        /* compact info cards */
        .mini-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 8px 0 10px
        }

        .mini-col {
            display: table-cell;
            vertical-align: top;
            padding-right: 10px
        }

        .mini-col:last-child {
            padding-right: 0
        }

        .mini-box,
        .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0 10px 8px;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .mini-title,
        .info-title {
            margin: 0;
            padding: 6px 10px;
            font-weight: 700;
            background: #01305a;
            color: #fff;
            border-bottom: 2px solid #0b2a3f;
        }

        .info-body {
            padding: 8px 10px 10px;
        }

        .info-subtitle {
            font-weight: 700;
            background: #eaf2fb;
            color: #0b2a3f;
            border-left: 4px solid #01305a;
            padding: 4px 8px;
            margin: 8px 0 6px;
            /* align with card edges */
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .mini-line {
            margin: 2px 0
        }

        /* stacked full-width cards */
        .mini-grid.stack .mini-col {
            display: block;
            width: 100%;
            padding-right: 0;
            margin-bottom: 8px;
        }

        .mini-grid.stack .mini-col:last-child {
            margin-bottom: 0;
        }

        /* safe 2-column layout inside the Billing card (works well in DomPDF) */
        .mini-split {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 6px;
        }

        .mini-split .cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .mini-split .cell.right {
            text-align: right;
            vertical-align: bottom;
        }

        /* stacked full-width cards already exist; add a 2-up split inside a box */
        .pair-split {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .pair-split .cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .pair-split .cell.right {
            padding-right: 0;
            text-align: left;
        }

        /* keep text natural */

        /* 3) Full-page background that repeats on all pages, behind content */
        .page-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
        }

        .page-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Optional: keep a centered content width (not required for the margin fix) */
        .content {
            max-width: 190mm;
            margin: 0 auto;
        }

        /* top meta grid */
        .grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .grid .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }

        .field {
            margin: 3px 0;
        }

        .label {
            font-weight: 700;
            color: #374151;
        }

        .value {
            color: #111827;
            word-break: break-word;
        }

        /* payment / delivery box */
        .box {
            margin: 10px 0 12px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .box-title {
            font-weight: 700;
            margin-bottom: 6px;
            color: #0f172a;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
            page-break-inside: avoid;
        }

        tr {
            page-break-inside: auto;
            break-inside: auto;
        }

        /* blue header band */
        thead th {
            background: #01305a;
            /* UTS blue */
            color: #fff;
            border-bottom: 2px solid #0b2a3f;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: .03em;
            white-space: nowrap;
            line-height: 1.15;
        }

        .th-sub {
            display: inline-block;
            margin-top: 1px;
            font-size: 9px;
            font-weight: 700;
            color: #dbe7f1;
        }

        th,
        td {
            padding: 4px 4px;
            /* slightly tighter */
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            text-overflow: clip;
            overflow: visible
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* ---------- Table width tuning so columns don’t collide ---------- */
        /* Give SKU and MAKE a bit more room; Description still gets the most */
        .col-no {
            width: 4%;
            text-align: center;
        }

        .col-sku {
            width: 14%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .col-make {
            width: 12%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .col-desc {
            width: 28%;
        }

        .col-qty {
            width: 7%;
            text-align: right;
            white-space: nowrap;
        }

        .col-unitp {
            width: 15%;
            text-align: right;
            white-space: nowrap;
        }

        /* more room for “AED 40,000.00” */
        .col-amt {
            width: 20%;
            text-align: right;
            white-space: nowrap;
        }

        /* Don’t clip in totals or money cells */
        tfoot th,
        tfoot td,
        td.col-amt,
        td.col-unitp {
            overflow: visible;
        }

        tbody td.col-desc {
            font-size: 9.8px;
            /* body is 11px; this makes 3–4 words/line typical */
            line-height: 1.26;
            white-space: normal;
            word-break: normal;
            overflow-wrap: anywhere;
            hyphens: auto;
            overflow: visible;
            /* ensure multi-line text isn’t clipped */
        }

        tbody tr {
            page-break-inside: avoid;
        }

        tfoot th,
        tfoot td {
            white-space: nowrap;
            font-weight: 800;
            color: #0f172a;
        }

        tfoot tr:last-child th,
        tfoot tr:last-child td {
            border-top: 2px solid #e5e7eb;
        }

        .after-table {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .readflat {
            margin: 0;
            padding-left: 16px;
            list-style: disc;
        }

        .readflat li {
            margin: 2px 0;
        }

        .sum-label {
            background: #01305a;
            color: #fff;
            padding: 6px 10px;
            margin: 12px 0 6px;
            border-radius: 6px;
            border-bottom: 2px solid #0b2a3f;
            font-weight: 700;
        }

        /* Supplier & Terms and Signatures */
        .section-title {
            background: #01305a;
            color: #fff;
            padding: 6px 10px;
            font-weight: 700;
            border-radius: 6px 6px 0 0;
        }

        .box-terms {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0;
            /* no extra spacing */
            page-break-inside: avoid;
        }

        /* Show exactly what the user typed */
        .terms-plain {
            white-space: pre-wrap;
            /* preserve spaces + wrap */
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.8px;
            line-height: 1.45;
            margin: 8px 10px 10px;
            /* inside the border */
            hyphens: manual;
            /* don’t auto-hyphenate words */
        }

        /* Info grid (Supplier Information + Payment/Delivery) */
        .info-grid.equal {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 8px 0 10px;
        }

        .info-grid.equal .info-col {
            display: table-cell;
            vertical-align: top;
            padding: 0 8px;
            /* ← gutter */
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .info-grid.equal .info-col:first-child {
            padding-left: 0;
        }

        .info-grid.equal .info-col:last-child {
            padding-right: 0;
        }

        /* Draw the card on the inner box again (no hacks) */
        .info-grid.equal .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            /* so the title rounds perfectly */
            padding: 0;
            /* we'll pad the body, not the title */
            min-height: 78mm;
            page-break-inside: avoid;
        }

        .info-row {
            display: table;
            width: 100%;
            table-layout: auto;
            margin: 2px 0;
        }

        .info-label,
        .info-sep,
        .info-value {
            display: table-cell;
            vertical-align: top;
        }

        .info-label {
            width: 140px;
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
        }

        .info-sep {
            width: 10px;
            padding: 0 2px;
        }

        .info-value {
            color: #111827;
            word-break: break-word;
            white-space: normal;
        }

        .sig-section {
            margin-top: 18mm;
            /* add some breathing room */
            page-break-inside: avoid;
        }

        .sig-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .sig-col {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
            padding-right: 12px;
        }

        .sig-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sig-name {
            margin-top: 6px;
            font-weight: 700;
        }

        .sig-meta {
            color: #374151;
            margin-top: 2px;
        }

        .sig-label {
            font-weight: 700;
            margin-top: 6px;
        }

        .sig-blank {
            height: 10mm;
        }

        .sig-value {
            margin-top: 2px;
            color: #111827;
        }

        .sig-gap {
            height: 22mm;
        }
    </style>
</head>

<body>
    @php
    use Illuminate\Support\Carbon;

    // ---------- helpers (Indonesian formatting) ----------
    $fmt4 = function ($n) {
    $s = number_format((float)($n ?? 0), 4, '.', ','); // e.g. 34,111.7650
    // trim trailing zeros and the dot if not needed
    $s = preg_replace('/\.?0+$/', '', $s);
    return 'IDR ' . $s; // e.g. 34,111.765
    };

    $fmt0 = function ($n) { // integers (no dp)
    $i = (int) ($n ?? 0);
    // -> 1,234,567 (comma thousands)
    return 'IDR ' . number_format($i, 0, '.', ',');
    };

    // safe int (handles strings with separators just in case)
    $toInt = fn($v) => (int) preg_replace('/[^\d]/', '', (string) $v);

    // date
    $formattedDate = $po->po_date ? Carbon::parse($po->po_date)->format('d-m-Y') : '—';

    // ---------- per-row amounts (rounded) ----------
    $rows = $po->rows ?? collect();
    $lines = [];
    foreach ($rows as $r) {
    $line = (int) round((float)($r->price_aed ?? 0) * (float)($r->qty ?? 0), 0);
    $lines[] = $line;
    }

    // ---------- totals (match show.blade.php logic) ----------
    $toNum = fn($v) => (float) preg_replace('/[^\d.]/', '', (string) $v);

    // Recompute subtotal from SAVED DB values (same as show.blade.php)
    $rows = $po->rows ?? collect();
    $subtotalIDR = $rows->sum(function ($r) use ($toNum) {
    $unit = $toNum($r->price_aed ?? 0);
    $qty = $toNum($r->qty ?? 0);
    return (int) round($unit * $qty);
    });

    // Use SAVED tax fields (same as show.blade.php)
    $kindSaved = strtolower($po->tax_kind ?? 'ppn');
    $manualSaved = (int) round((float) ($po->ppn_rate ?? 0));
    $taxIDR = ($kindSaved === 'none') ? 0 : ($manualSaved > 0 ? $manualSaved : 0);
    $totalIDR = (int) $subtotalIDR + (int) $taxIDR;

    // labels
    $taxLabelMap = ['ppn' => 'PPN', 'pph' => 'PPH', 'vat' => 'PPN'];
    $taxLabel = ($kindSaved === 'none') ? '' : ($taxLabelMap[$kindSaved] ?? strtoupper($kindSaved));
    $showTaxRow = $taxLabel !== '';

    // ---------- Amount in Words (match show.blade.php with fallback) ----------
    $totalInt = (int) $totalIDR;

    // Fallback terbilang if intl is missing
    $terbilang = function($n) {
        $n = (int) $n;
        $s = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
        $fn = function($x) use (&$fn,$s){
        if ($x < 12) return $s[$x];
            if ($x < 20) return $fn($x-10).' belas';
            if ($x < 100) return $fn(intval($x/10)).' puluh'.($x%10?' '.$fn($x%10):'');
            if ($x < 200) return ' seratus'.($x-100?' '.$fn($x-100):'');
            if ($x < 1000) return $fn(intval($x/100)).' ratus'.($x%100?' '.$fn($x%100):'');
            if ($x < 2000) return ' seribu'.($x-1000?' '.$fn($x-1000):'');
            if ($x < 1000000) return $fn(intval($x/1000)).' ribu'.($x%1000?' '.$fn($x%1000):'');
            if ($x < 1000000000) return $fn(intval($x/1000000)).' juta'.($x%1000000?' '.$fn($x%1000000):'');
            if ($x < 1000000000000) return $fn(intval($x/1000000000)).' miliar'.($x%1000000000?' '.$fn($x%1000000000):'');
            return $fn(intval($x/1000000000000)).' triliun'.($x%1000000000000?' '.$fn($x%1000000000000):'');
        };
        return $fn(max(0,$n));
    };

        try {
            if (!class_exists(\NumberFormatter::class)) throw new \Exception(' intl missing');
            $fmt=new \NumberFormatter('id', \NumberFormatter::SPELLOUT);
            $words=$fmt->format($totalInt);
            if ($words === false) throw new \Exception('spellout failed');
            $amountWords = ucfirst($words).' rupiah';
        } catch (\Throwable $e) {
            $amountWords = ucfirst($terbilang($totalInt)).' rupiah';
        }
        @endphp

        <div class="page-bg">
            @if(!empty($bgData))
            <img src="data:image/png;base64,{{ $bgData }}" alt="">
            @endif
        </div>

        <div class="content">
            <div class="headbar">
                <div class="head-left">
                    <h1>Purchase Order</h1>
                </div>
            </div>

            <!-- === TWO CARDS: LEFT = Supplier, RIGHT = Buyer (Billing + Ship To) === -->
            <div class="info-grid equal">
                <!-- LEFT: Supplier Information -->
                <div class="info-col">
                    <div class="info-box">
                        <div class="info-title">Supplier Information</div>
                        <div class="info-body">
                            <div class="info-row">
                                <span class="info-label">Company Name</span>
                                <span class="info-sep">:</span>
                                <span class="info-value">{{ $po->sup_company }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Company Address</span>
                                <span class="info-sep">:</span>
                                <span class="info-value">{!! nl2br(e($po->sup_address)) !!}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone Number</span>
                                <span class="info-sep">:</span>
                                <span class="info-value">{{ $po->sup_phone }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">E-mail</span>
                                <span class="info-sep">:</span>
                                <span class="info-value">{{ $po->sup_email }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">NPWP</span>
                                <span class="info-sep">:</span>
                                <span class="info-value">{{ $po->sup_npwp }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Buyer Information (with plain Billing Address + Ship To) -->
                <div class="info-col">
                    <div class="info-box">
                        <div class="info-title">Buyer Information</div>
                        <div class="info-body">
                            <div class="info-subtitle">Billing Address</div>
                            <div class="info-row">
                                <span class="info-label">PO Number</span><span class="info-sep">:</span>
                                <span class="info-value">{{ $po->po_number }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address</span><span class="info-sep">:</span>
                                <span class="info-value">{!! $po->address ? nl2br(e($po->address)) : '—' !!}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">NPWP</span><span class="info-sep">:</span>
                                <span class="info-value">1000.0000.0070.1243</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date</span><span class="info-sep">:</span>
                                <span class="info-value">{{ $formattedDate }}</span>
                            </div>

                            <div class="info-subtitle" style="margin-top:10px;">Ship To</div>
                            <div class="info-row">
                                <span class="info-label">Company</span><span class="info-sep">:</span>
                                <span class="info-value"><strong>PT. UNIVERSAL TRADE SERVICES</strong></span>
                            </div>
                            @php
                            $rec = trim((string)($po->ship_to_recipient ?? ''));
                            $addr = trim((string)($po->ship_to_address ?? ''));
                            $nitku = '1000000000701243000001';
                            @endphp
                            @if($rec !== '')
                            <div class="info-row">
                                <span class="info-label">Recipient</span><span class="info-sep">:</span>
                                <span class="info-value">{{ $rec }}</span>
                            </div>
                            @endif
                            <div class="info-row">
                                <span class="info-label">Address</span><span class="info-sep">:</span>
                                <span class="info-value">
                                    @if($addr !== ''){!! nl2br(e($addr)) !!}, @endif NITKU: {{ $nitku }}
                                </span>
                            </div>
                            @if(!empty($po->ship_to_phone))
                            <div class="info-row">
                                <span class="info-label">Phone</span><span class="info-sep">:</span>
                                <span class="info-value">{{ $po->ship_to_phone }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="center col-no">#</th>
                        <th class="col-sku">
                            ITEM<br><span class="th-sub">NUMBER</span>
                        </th>
                        <th class="col-make">MAKE</th>
                        <th class="col-desc">DESCRIPTION</th>
                        <th class="right col-qty">QTY</th>
                        <th class="right col-unitp">
                            UNIT PRICE<br><span class="th-sub">IDR</span>
                        </th>
                        <th class="right col-amt">
                            TOTAL PRICE<br><span class="th-sub">IDR</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                    @php
                    // formatters you defined earlier
                    // $fmt4 = fn($n) => number_format((float)$n, 4, '.', ','); // example
                    // $fmt0 = fn($n) => 'IDR ' . number_format((int)$n, 0, ',', '.');

                    $unitStr = $fmt4($r->price_aed ?? 0); // unit price 4dp
                    $qtyStr = rtrim(rtrim((string)($r->qty ?? '0'), '0'), '.'); // clean qty

                    // Use stored integer amount; fallback recompute only if null
                    $lineInt = (int) round((float)($r->price_aed ?? 0) * (float)($r->qty ?? 0), 0);

                    $lineStr = $fmt0($lineInt); // "IDR 223,243"
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $r->sku }}</td>
                        <td>{{ $r->brand }}</td>
                        <td class="col-desc">{{ $r->description }}</td>
                        <td class="right">{{ rtrim(rtrim($qtyStr, '0'), '.') ?: '0' }}</td>
                        <td class="right">{{ $unitStr }}</td>
                        <td class="right">{{ $fmt0($lineInt) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="center muted">No rows.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="right">Subtotal</th>
                        <td class="right"><strong>{{ $fmt0($subtotalIDR) }}</strong></td>
                    </tr>
                    @if($showTaxRow)
                    <tr>
                        <th colspan="6" class="right">{{ $taxLabel }}</th>
                        <td class="right"><strong>{{ $fmt0($taxIDR) }}</strong></td>
                    </tr>
                    @endif
                    <tr>
                        <th colspan="6" class="right">Total</th>
                        <td class="right"><strong>{{ $fmt0($totalIDR) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="after-table">
                <div class="sum-label">Amount in Words</div>
                <div>{{ $amountWords }}</div>
            </div>

            @php
            $terms = (string)($po->conditions_terms ?? '');
            // tabs → 4 spaces
            $terms = str_replace("\t", str_repeat(' ', 4), $terms);
            $terms_html = e($terms);
            // preserve leading spaces per line
            $terms_html = preg_replace_callback('/(^|\r?\n)([ \t]+)/m', function ($m) {
            $lead = str_replace("\t", str_repeat(' ', 4), $m[2]);
            return $m[1] . str_repeat('&nbsp;', strlen($lead));
            }, $terms_html);
            @endphp

            @if (trim($terms) !== '')
            <div class="after-table box-terms">
                <div class="section-title">Conditions &amp; Terms</div>
                <pre class="terms-plain">{!! rtrim($terms_html) !!}</pre>
            </div>
            @endif

            <div class="sig-section">
                <div class="sig-grid">
                    <div class="sig-col">
                        <div class="sig-name">Universal Trade Services</div>

                        <div class="sig-label">Name:</div>
                        <div class="sig-label">Position:</div>
                        <div class="sig-blank"></div> <!-- handwriting space -->

                        <div class="sig-meta" style="margin-top:6mm;">Signature</div>
                    </div>

                    <div class="sig-col" style="padding-left:12px;">
                        <div class="sig-title">This PO is accepted by</div>
                        <div class="sig-meta">Name:</div>
                        <div class="sig-meta">Supplier Signature &amp; Stamp</div>
                    </div>
                </div>
            </div>

        </div>
</body>

</html>