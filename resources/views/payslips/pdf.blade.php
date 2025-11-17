<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payslip->nama }}</title>
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

        /* Same inner margin as PO so it sits nicely on the background */
        body {
            margin: 60mm 18mm 28mm 18mm;
        }

        /* Full-page background like PO */
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

        /* Center main content like PO */
        .content {
            max-width: 175mm;
            margin: 0 auto;
        }

        /* Header */
        .headbar {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 8mm 0 6px;
        }

        .head-left,
        .head-right {
            display: table-cell;
            vertical-align: bottom;
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
            white-space: nowrap;
        }

        .head-sub {
            font-size: 10px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }

        /* Employee meta */
        .meta {
            margin: 8px 0 12px;
        }

        .meta-row {
            margin: 2px 0;
        }

        .meta-label {
            font-weight: 700;
            color: #374151;
        }

        .meta-value {
            color: #111827;
        }

        /* Two-column layout for Pendapatan / Potongan */
        .columns {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
        }

        .col {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .col:last-child {
            padding-right: 0;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 8px;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .row-label {
            font-size: 10px;
            color: #374151;
        }

        .row-value {
            font-size: 10px;
            color: #111827;
        }

        .row-strong {
            font-weight: 700;
        }

        .row-highlight {
            background: #f3f4f6;
            padding: 4px 6px;
            border-radius: 4px;
            margin-top: 3px;
        }

        /* Footer (Terbilang + Dicetak) */
        .footer {
            margin-top: 14px;
            border-top: 1px dashed #e5e7eb;
            padding-top: 8px;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .footer-label {
            font-weight: 700;
            color: #6b7280;
            font-size: 10px;
        }

        .footer-value {
            font-size: 10px;
            text-align: right;
        }

        .net-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #6b7280;
        }

        .net-amount {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
    </style>
</head>

<body>
@php
    // net salary in words via your helper
    $amountWords = ucfirst(terbilang_id($payslip->gaji_bersih)) . ' rupiah';
@endphp

<div class="page-bg">
    @if(!empty($bgData))
        <img src="data:image/png;base64,{{ $bgData }}" alt="">
    @endif
</div>

<div class="content">
    {{-- Header --}}
    <div class="headbar">
        <div class="head-left">
            <h1>Payslip</h1>
            <p class="head-sub">
                Summary of net earnings and deductions for {{ $payslip->nama }}.
            </p>
        </div>
        <div class="head-right">
            <div class="net-label">Gaji Bersih</div>
            <div class="net-amount">
                {{ $fmtIDR($payslip->gaji_bersih) }}
            </div>
        </div>
    </div>

    {{-- Employee meta --}}
    <div class="meta">
        <div class="meta-row">
            <span class="meta-label">Nama</span>
            <span class="meta-value"> : {{ $payslip->nama }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Posisi Karyawan</span>
            <span class="meta-value"> : {{ $payslip->posisi_karyawan }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">NPWP</span>
            <span class="meta-value"> : {{ $payslip->npwp ?? '-' }}</span>
        </div>
    </div>

    {{-- Two columns: Pendapatan & Potongan --}}
    <table class="columns">
        <tr>
            {{-- Pendapatan --}}
            <td class="col">
                <div class="section-title">Pendapatan</div>
                <div class="box">
                    <div class="row">
                        <span class="row-label">Gaji</span>
                        <span class="row-value">{{ $fmtIDR($payslip->gaji) }}</span>
                    </div>
                    <div class="row">
                        <span class="row-label">JHT Perusahaan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jht_perusahaan) }}</span>
                    </div>
                    <div class="row">
                        <span class="row-label">JKK</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jkk) }}</span>
                    </div>
                    <div class="row">
                        <span class="row-label">JKM</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jkm) }}</span>
                    </div>
                    <div class="row">
                        <span class="row-label">JP Perusahaan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jp_perusahaan) }}</span>
                    </div>

                    <div class="row row-highlight row-strong">
                        <span class="row-label">BPJS Perusahaan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->bpjs_perusahaan) }}</span>
                    </div>
                </div>
            </td>

            {{-- Potongan --}}
            <td class="col">
                <div class="section-title">Potongan</div>
                <div class="box">
                    <div class="row">
                        <span class="row-label">JHT Karyawan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jht_karyawan) }}</span>
                    </div>
                    <div class="row">
                        <span class="row-label">JP Karyawan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->jp_karyawan) }}</span>
                    </div>

                    <div class="row row-highlight row-strong">
                        <span class="row-label">Subtotal Potongan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->subtotal_potongan) }}</span>
                    </div>

                    @if($payslip->pph21 > 0)
                        <div class="row">
                            <span class="row-label">PPh Ps. 21</span>
                            <span class="row-value">{{ $fmtIDR($payslip->pph21) }}</span>
                        </div>
                    @endif

                    <div class="row row-highlight row-strong">
                        <span class="row-label">Total Potongan</span>
                        <span class="row-value">{{ $fmtIDR($payslip->total_potongan) }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer: Terbilang + Dicetak --}}
    <div class="footer">
        <div class="footer-row">
            <span class="footer-label">Terbilang</span>
            <span class="footer-value">
                {{ $amountWords }}
            </span>
        </div>
        <div class="footer-row">
            <span class="footer-label">Dicetak pada</span>
            <span class="footer-value">
                {{ $payslip->printed_at?->translatedFormat('d F Y') }}
            </span>
        </div>
    </div>
</div>

</body>
</html>
