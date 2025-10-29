<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO {{ $po->po_number }}</title>
    <link rel="icon" href="{{ asset('images/UTS.png') }}">
    <style>
        /* 1) No page margins; we’ll reserve space with body margins instead */
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
            font-size: 20px
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

        .mini-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            page-break-inside: avoid
        }

        .mini-title {
            font-weight: 700;
            margin: 0 0 6px;
            color: #0f172a
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

        /* ensures AED isn’t clipped */

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
        }

        .sum-label {
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: 700;
        }

        /* Supplier & Terms and Signatures */
        .box-terms {
            margin-top: 10px;
            page-break-inside: avoid;
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

        .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            page-break-inside: avoid;
        }

        .info-title {
            font-weight: 700;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin: 2px 0;
        }

        .info-label {
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
        }

        .info-label::after {
            content: ":";
            margin-left: 2px;
        }

        .info-value {
            color: #111827;
            flex: 1 1 auto;
            word-break: break-word;
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

        .sig-line {
            margin-top: 18px;
            border-top: 1px solid #cbd5e1;
            height: 0;
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
            <div class="head-right">Created: {{ $po->created_at?->format('d-m-Y H:i') }}</div>
        </div>

        {{-- Stacked full-width cards: Billing (with 2-column row), then ORDER BY, then SHIP TO --}}
        @php
        $rateVal = is_null($po->ppn_rate) ? 0 : (float)$po->ppn_rate;
        $rateTxt = rtrim(rtrim(number_format($rateVal, 2, '.', ''), '0'), '.');
        @endphp

        <div class="mini-grid stack">
            {{-- 1) Billing address (full width) – Address moved below PO Number; PPN/PPH+Date on right --}}
            <div class="mini-col">
                <div class="mini-box">
                    <div class="mini-title">Billing address</div>

                    <div class="mini-split">
                        {{-- LEFT: Supplier + PO + Address (in that order) --}}
                        <div class="cell">
                            <div class="mini-line"><strong>Supplier Name:</strong> {{ $po->prepared_by }}</div>
                            <div class="mini-line"><strong>PO Number:</strong> {{ $po->po_number }}</div>
                            <div class="mini-line">
                                <strong>Address:</strong>
                                {!! $po->address ? nl2br(e($po->address)) : '—' !!}
                            </div>
                        </div>

                        {{-- RIGHT: PPN/PPH and Date --}}
                        <div class="cell right">
                            @php
                                use Illuminate\Support\Carbon;
                                $rateVal = is_null($po->ppn_rate) ? 0 : (float)$po->ppn_rate;
                                $rateTxt = rtrim(rtrim(number_format($rateVal, 2, '.', ''), '0'), '.');
                                $formattedDate = $po->po_date ? Carbon::parse($po->po_date)->format('d-m-Y') : '—';
                            @endphp
                            <div class="mini-line"><strong>PPN / PPH:</strong> {{ $rateTxt }}%</div>
                            <div class="mini-line"><strong>Date:</strong> {{ $formattedDate }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2) One combined box: SHIP TO (left) + ORDER BY (right) inside the same border --}}
            <div class="mini-col">
                <div class="mini-box">
                    <div class="pair-split">
                        {{-- LEFT: SHIP TO --}}
                        <div class="cell">
                            <div class="mini-title">SHIP TO</div>
                            <div class="mini-line">Greenwich Business Park Blok E9</div>
                            <div class="mini-line">Jl. Bumi Botanika, Kel. Lengkong Kulon, Kec. Pagedangan</div>
                            <div class="mini-line">Kab. Tangerang, 15331</div>
                            <div class="mini-line">Phone: +62-819-1938-4545 (Kevin)</div>
                            <div class="mini-line">NITKU: 1000000000701243000001</div>
                        </div>

                        {{-- RIGHT: ORDER BY (inside same box) --}}
                        <div class="cell right">
                            <div class="mini-title">ORDER BY</div>
                            <div class="mini-line"><strong>PT. UNIVERSAL TRADE SERVICES</strong></div>
                            <div class="mini-line"><strong>NPWP:</strong> 1000.0000.0070.1243</div>
                        </div>
                    </div>
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
                        <span class="info-value">{{ $po->sup_company }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Company Address</span>
                        <span class="info-value">{!! nl2br(e($po->sup_address)) !!}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value">{{ $po->sup_phone }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">E-mail</span>
                        <span class="info-value">{{ $po->sup_email }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact Person</span>
                        <span class="info-value">{{ $po->sup_contact_person }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact Phone</span>
                        <span class="info-value">{{ $po->sup_contact_phone }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact Email</span>
                        <span class="info-value">{{ $po->sup_contact_email }}</span>
                    </div>
                </div>
            </div>

            {{-- Right: Payment / Delivery (your existing values) --}}
            <div class="info-col">
                <div class="info-box">
                    <div class="info-title">Payment / Delivery</div>

                    <div class="info-row">
                        <span class="info-label">Payment Terms</span>
                        <span class="info-value">100% Advance payment to be made in bank before dispatch of delivery.</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Delivery Time</span>
                        <span class="info-value">14 working days from the date of payment</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Delivery Terms</span>
                        <span class="info-value">Ex-works Dubai</span>
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
                        AMOUNT<br><span class="th-sub">IDR</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                @php
                $unitPriceFils = (int) ($r->price_aed ?? 0);
                $qty = (float) ($r->qty ?? 0);
                $rowAmtFils = (int) round($unitPriceFils * $qty);
                /**
                * IDR formatting:
                * - Divide by 100 (values stored in “fils”/cents)
                * - No decimal places for Rupiah
                * - Thousands separator: dot (.)
                * - Decimal separator: comma (,)
                */
                $fmtIDR = fn (int $f) => 'IDR ' . number_format($f / 100, 0, ',', '.');
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $r->sku }}</td>
                    <td>{{ $r->brand }}</td>
                    <td class="col-desc">{{ $r->description }}</td>
                    <td class="right">{{ $r->qty ?: 0 }}</td>
                    <td class="right">{{ $unitPriceFils ? $fmtIDR($unitPriceFils) : '' }}</td>
                    <td class="right">{{ $fmtIDR($rowAmtFils) }}</td>
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
                    <td class="right"><strong>{{ $subtotal }}</strong></td>
                </tr>
                <tr>
                    <th colspan="6" class="right">{{ $taxLabel }}</th>
                    <td class="right"><strong>{{ $ppn }}</strong></td>
                </tr>
                <tr>
                    <th colspan="6" class="right">Total</th>
                    <td class="right"><strong>{{ $total }}</strong></td>
                </tr>
            </tfoot>
        </table>

        @php
        $amountWordsDisplay = is_string($amountWords)
        ? preg_replace('/\bAED\b/i', 'IDR', $amountWords)
        : $amountWords;
        @endphp
        <div class="after-table">
            <div class="sum-label">Amount in Words</div>
            <div>{{ $amountWordsDisplay }}</div>
        </div>

        @php
        $supplierBrand = trim((string)($po->sup_company ?? '')); // Supplier Information → Company Name
        if ($supplierBrand === '') $supplierBrand = 'the Supplier';
        @endphp

        <div class="after-table" style="padding-top: 10px;">
            <div class="sum-label">Conditions and terms</div>
            <ul class="readflat">
                <li>Universal Trade Services will not be responsible for any additional cost other than the one mentioned in the Purchase Order.</li>
                <li>The Purchase Order is in accordance with the relevant laws, regulations, and national standards of the United Arab Emirates.</li>
                <li>In case of work completion delayed by the failure on the part of the {{ $supplierBrand }} in the time specified and agreed, The {{ $supplierBrand }} is not liable to pay delay penalty to Universal Trade Services.</li>
                <li>We certify the purchase has been made from authorized source which is directly manufacturer {{ $supplierBrand }} and product supplied by the original manufacturer. The purchase is made by Universal Trade Services who intend to resell as authorized reseller.</li>
                <li>All works shall be carried out in strict accordance with all relevant HSE legislation all the time.</li>
                <li>Universal Trade Services has reserved the right to terminate this agreement because of the delays or quality.</li>
            </ul>
        </div>

        @php
        // Right-side heading should be the Supplier Information → Company Name
        $supplierBrand = trim((string)($po->sup_company ?? ''));
        if ($supplierBrand === '') $supplierBrand = 'the Supplier';
        @endphp

        <div class="sig-section">
            <div class="sig-grid">
                {{-- Left: Buyer (fixed) --}}
                <div class="sig-col">
                    <div class="sig-name">Universal Trade Services</div>
                    <div class="sig-meta">&nbsp;</div>
                    <!-- <div class="sig-line"></div> -->
                    <div class="sig-meta">Signature</div>
                </div>

                {{-- Right: Accepted by supplier company --}}
                <div class="sig-col" style="padding-left:12px;">
                    <div class="sig-title">This PO is accepted by {{ $supplierBrand }}</div>
                    <div class="sig-meta">Name:</div>
                    <div class="sig-meta">Supplier Signature &amp; Stamp</div>
                    <!-- <div class="sig-line"></div> -->
                </div>
            </div>
        </div>

    </div>
</body>

</html>