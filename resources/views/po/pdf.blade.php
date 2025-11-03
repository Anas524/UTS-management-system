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
            /* top=0 so the band sits flush */
            page-break-inside: avoid;
            overflow: hidden;
            /* clip band corners cleanly */
        }

        .mini-title,
        .info-title {
            font-weight: 700;
            margin: 0 0 8px;
            /* reset */
            background: #0f3d56;
            /* UTS blue */
            color: #fff;
            padding: 6px 10px;
            margin-left: -10px;
            /* stretch to box edges */
            margin-right: -10px;
            border-bottom: 2px solid #0b2a3f;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
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
            background: #0f3d56;
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
            background: #0f3d56;
            color: #fff;
            padding: 6px 10px;
            margin: 12px 0 6px;
            border-radius: 6px;
            border-bottom: 2px solid #0b2a3f;
            font-weight: 700;
        }

        /* Supplier & Terms and Signatures */
        .section-title {
            background: #0f3d56;
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
        .info-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 8px 0 10px;
        }

        .info-col {
            display: table-cell;
            width: 100%;
            vertical-align: top;
            padding-right: 10px;
        }

        .info-col:last-child {
            padding-right: 0;
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
            gap: 0;
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
    </style>
</head>

<body>
    @php
    // Rows & money helpers
    $rows = $po->rows ?? collect();

    // format "IDR 12.345" (no decimals for Rupiah)
    $fmtIDR = fn (int $rupiah) => 'IDR ' . number_format($rupiah, 0, ',', '.');

    // Subtotal in rupiah: sum(qty * unit_rupiah)
    $subtotal = $rows->sum(function ($r) {
    $qty = (float) ($r->qty ?? 0);
    $unitRupiah = (int) ($r->price_aed ?? 0); // stored as whole rupiah
    return (int) round($qty * $unitRupiah);
    });

    $rate = (float) ($po->ppn_rate ?? 0);
    $taxAmt = (int) round($subtotal * $rate / 100);
    $total = $subtotal + $taxAmt;

    use Illuminate\Support\Carbon;
    $formattedDate = $po->po_date ? Carbon::parse($po->po_date)->format('d-m-Y') : '—';

    // Map the selectable kinds to a readable label
    $kind = strtolower((string)($po->tax_kind ?? 'ppn_pph'));
    $kindFull = match ($kind) {
    'pph' => 'Pajak Penghasilan (PPH)',
    'ppn' => 'Pajak Pertambahan Nilai (PPN)',
    'none' => '',
    default => '',
    };

    // "PPN", "PPH", "VAT", or "PPN / PPH" + rate
    $rateTxt = rtrim(rtrim(number_format((float)$po->ppn_rate, 2, '.', ''), '0'), '.');
    $showTaxRow = ($kind !== 'none');
    $taxLabel = $showTaxRow ? ($kindFull.' '.$rateTxt.'%') : '';

    // Amount-in-words (ID) fallback if controller didn't pass it
    if (!isset($amountWords) || !is_string($amountWords) || $amountWords === '') {
    $n = max((int) floor($total), 0); // use $total in rupiah
    $s = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    $terbilang = function($x) use (&$terbilang, $s) {
    if ($x < 12) return $s[$x];
        if ($x < 20) return $terbilang($x-10).' belas';
        if ($x < 100) return $terbilang(intval($x/10)).' puluh'.($x%10 ? ' ' .$terbilang($x%10) : '' );
        if ($x < 200) return 'seratus' .($x-100 ? ' ' .$terbilang($x-100) : '' );
        if ($x < 1000) return $terbilang(intval($x/100)).' ratus'.($x%100 ? ' ' .$terbilang($x%100) : '' );
        if ($x < 2000) return 'seribu' .($x-1000 ? ' ' .$terbilang($x-1000) : '' );
        if ($x < 1000000) return $terbilang(intval($x/1000)).' ribu'.($x%1000 ? ' ' .$terbilang($x%1000) : '' );
        if ($x < 1000000000) return $terbilang(intval($x/1000000)).' juta'.($x%1000000 ? ' ' .$terbilang($x%1000000) : '' );
        if ($x < 1000000000000) return $terbilang(intval($x/1000000000)).' miliar'.($x%1000000000 ? ' ' .$terbilang($x%1000000000) : '' );
        return $terbilang(intval($x/1000000000000)).' triliun'.($x%1000000000000 ? ' ' .$terbilang($x%1000000000000) : '' );
        };
        $amountWords=ucfirst($terbilang($n)).' rupiah';
        }

        @endphp

        <div class="page-bg">
        @if($bgData)
        <img src="data:image/png;base64,{{ $bgData }}" alt="">
        @endif
        </div>

        <div class="content">
            <div class="headbar">
                <div class="head-left">
                    <h1>Purchase Order</h1>
                </div>
                <!-- <div class="head-right">Created: {{ $po->created_at?->format('d-m-Y H:i') }}</div> -->
            </div>

            <div class="mini-grid stack">
                {{-- Billing address (full width) – Address moved below PO Number; PPN/PPH+Date on right --}}
                <div class="mini-col">
                    <div class="mini-box">
                        <div class="mini-title">Billing Address</div>

                        <div class="mini-split">
                            {{-- LEFT: PO Number + Address --}}
                            <div class="cell">
                                <div class="mini-line"><strong>PO Number:</strong> {{ $po->po_number }}</div>
                                <div class="mini-line">
                                    <strong>Address:</strong>
                                    {!! $po->address ? nl2br(e($po->address)) : '—' !!}
                                </div>
                                <div class="mini-line"><strong>NPWP:</strong> 1000.0000.0070.1243</div>
                            </div>

                            {{-- RIGHT: PPN/PPH and Date --}}
                            <div class="cell right">
                                <div class="mini-line"><strong>Date:</strong> {{ $formattedDate }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mini-col">
                    <div class="mini-box">
                        <div class="mini-title">Ship To</div>

                        <div class="mini-line"><strong>PT. UNIVERSAL TRADE SERVICES</strong></div>

                        @php $rec = trim((string)($po->ship_to_recipient ?? '')); @endphp
                        @if($rec !== '')
                        <div class="mini-line"><strong>Recipient:</strong> {{ $rec }}</div>
                        @endif

                        <div class="mini-line">
                            @php
                            $addr = trim((string)($po->ship_to_address ?? ''));
                            $nitku = '1000000000701243000001';
                            @endphp

                            @if($addr !== '')
                            {!! nl2br(e($addr)) !!}, NITKU: {{ $nitku }}
                            @else
                            NITKU: {{ $nitku }}
                            @endif
                        </div>

                        @if(!empty($po->ship_to_phone))
                        <div class="mini-line">Phone: {{ $po->ship_to_phone }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Supplier Information + Payment/Delivery (two-column cards) --}}
            <div class="info-grid">
                {{-- Left: Supplier Information --}}
                <div class="info-col">
                    <div class="info-box">
                        <div class="info-title">Supplier Information</div>

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

                @php
                $payTerms = trim((string)($po->payment_terms ?? '100% Advance payment to be made in bank before dispatch of delivery.'));
                $delTime = trim((string)($po->delivery_time ?? '14 working days from the date of payment'));
                $delTerms = trim((string)($po->delivery_terms ?? 'Ex-works Dubai'));
                @endphp

                {{-- Right: Payment / Delivery (your existing values) --}}
                <div class="info-col">
                    <div class="info-box">
                        <div class="info-title">Payment / Delivery</div>

                        <div class="info-row">
                            <span class="info-label">Payment Terms</span>
                            <span class="info-sep">:</span>
                            <span class="info-value">{{ $payTerms }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Time</span>
                            <span class="info-sep">:</span>
                            <span class="info-value">{{ $delTime }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Terms</span>
                            <span class="info-sep">:</span>
                            <span class="info-value">{{ $delTerms }}</span>
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
                    $unitRupiah = (int) ($r->price_aed ?? 0); // whole rupiah
                    $qty = (float) ($r->qty ?? 0);
                    $rowAmt = (int) round($unitRupiah * $qty);
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $r->sku }}</td>
                        <td>{{ $r->brand }}</td>
                        <td class="col-desc">{{ $r->description }}</td>
                        <td class="right">{{ $r->qty ?: 0 }}</td>
                        <td class="right">{{ $unitRupiah ? $fmtIDR($unitRupiah) : 'IDR 0' }}</td>
                        <td class="right">{{ $fmtIDR($rowAmt) }}</td>
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
                        <td class="right"><strong>{{ $fmtIDR($subtotal) }}</strong></td>
                    </tr>
                    @if($showTaxRow)
                    <tr>
                        <th colspan="6" class="right">{{ $taxLabel }}</th>
                        <td class="right"><strong>{{ $fmtIDR($taxAmt) }}</strong></td>
                    </tr>
                    @endif
                    <tr>
                        <th colspan="6" class="right">Total</th>
                        <td class="right"><strong>{{ $fmtIDR($total) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="after-table">
                <div class="sum-label">Amount in Words</div>
                <div>{{ $amountWords }}</div>
            </div>

            @php
            $terms = (string)($po->conditions_terms ?? '');

            // Replace each TAB with four spaces
            $terms = str_replace("\t", str_repeat(' ', 4), $terms);

            // Escape for HTML
            $terms_html = e($terms);

            // Convert leading spaces/tabs on each line to &nbsp; so DomPDF keeps indents
            // (^|\r?\n)([ \t]+) => start-of-text/newline + 1+ spaces/tabs
            $terms_html = preg_replace_callback('/(^|\r?\n)([ \t]+)/m', function ($m) {
            $lead = str_replace("\t", str_repeat(' ', 4), $m[2]); // safety if tabs slipped through
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
                    {{-- Left: Buyer (fixed) --}}
                    <div class="sig-col">
                        <div class="sig-name">Universal Trade Services</div>
                        <div class="sig-meta">&nbsp;</div>
                        <div class="sig-meta">Signature</div>
                    </div>

                    {{-- Right: Accepted by supplier company --}}
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