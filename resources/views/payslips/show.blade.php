@extends('layouts.app')

@section('title', 'Payslip - '.$payslip->nama)

@push('head-scripts')
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                // Important: don't reset your existing CSS
                preflight: false,
            },
            theme: {
                extend: {
                    colors: {
                        utsBlue: '#0f172a',
                        utsGold: '#f5b91f',
                    },
                    borderRadius: {
                        'xl2': '1.25rem',
                    },
                },
            },
        }
    </script>
@endpush

@section('content')
@php
$fmtIDR = fn($n) => 'IDR '.number_format((float) $n, 0, '.', ',');
@endphp

<div class="font-plus pay-page min-h-screen bg-slate-50/60 py-10">
    <div class="max-w-4xl mx-auto px-4 space-y-6">

        {{-- Top bar --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Payslip</h1>
                <p class="font-plus mt-1 text-xs text-slate-500">
                    Summary of net earnings and deductions for {{ $payslip->nama }}.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('payslips.index') }}"
                    class="inline-flex cursor-pointer items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                    ← Back to list
                </a>

                <a href="{{ route('payslips.pdf', $payslip) }}"
                    target="_blank"
                    class="inline-flex cursor-pointer items-center rounded-full bg-utsBlue px-4 py-2 text-xs font-semibold text-white shadow-md shadow-slate-700/20 hover:bg-slate-900">
                    Export PDF
                </a>
            </div>
        </div>

        {{-- Payslip card --}}
        <div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200 ring-1 ring-slate-100 space-y-6">

            {{-- Header: employee meta + net salary --}}
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-slate-700 space-y-1">
                    <div><span class="font-semibold text-slate-500">Nama</span> : {{ $payslip->nama }}</div>
                    <div><span class="font-semibold text-slate-500">Posisi Karyawan</span> : {{ $payslip->posisi_karyawan }}</div>
                    <div><span class="font-semibold text-slate-500">NPWP</span> : {{ $payslip->npwp ?? '-' }}</div>
                </div>

                <div class="text-right md:text-right">
                    <div class="text-xs uppercase tracking-[0.18em] text-slate-400">
                        Gaji Bersih
                    </div>
                    <div class="mt-1 text-xl font-semibold text-emerald-600">
                        {{ $fmtIDR($payslip->gaji_bersih) }}
                    </div>
                </div>
            </div>

            {{-- Two columns: Pendapatan & Potongan --}}
            <div class="grid gap-8 md:grid-cols-2">
                {{-- Pendapatan --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        Pendapatan
                    </h3>

                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/40">
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>Gaji</span>
                            <span class="font-medium text-slate-900">{{ $fmtIDR($payslip->gaji) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JHT Perusahaan</span>
                            <span>{{ $fmtIDR($payslip->jht_perusahaan) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JKK</span>
                            <span>{{ $fmtIDR($payslip->jkk) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JKM</span>
                            <span>{{ $fmtIDR($payslip->jkm) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JP Perusahaan</span>
                            <span>{{ $fmtIDR($payslip->jp_perusahaan) }}</span>
                        </div>

                        <div class="flex items-center justify-between bg-slate-900/5 px-3 py-2.5 text-sm font-semibold">
                            <span>BPJS Perusahaan</span>
                            <span>{{ $fmtIDR($payslip->bpjs_perusahaan) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Potongan --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        Potongan
                    </h3>

                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/40">
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JHT Karyawan</span>
                            <span>{{ $fmtIDR($payslip->jht_karyawan) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>JP Karyawan</span>
                            <span>{{ $fmtIDR($payslip->jp_karyawan) }}</span>
                        </div>

                        <div class="flex items-center justify-between bg-slate-900/5 px-3 py-2.5 text-sm font-semibold">
                            <span>Subtotal Potongan</span>
                            <span>{{ $fmtIDR($payslip->subtotal_potongan) }}</span>
                        </div>

                        @if($payslip->pph21 > 0)
                        <div class="flex items-center justify-between px-3 py-2.5 text-sm">
                            <span>PPh Ps. 21</span>
                            <span>{{ $fmtIDR($payslip->pph21) }}</span>
                        </div>
                        @endif

                        <div class="flex items-center justify-between bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700">
                            <span>Total Potongan</span>
                            <span>{{ $fmtIDR($payslip->total_potongan) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer: terbilang + date --}}
            <div class="space-y-2 border-t border-dashed border-slate-200 pt-4 p-2 text-sm text-slate-700">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                    <span class="font-semibold text-slate-500">Terbilang</span>
                    <span class="sm:text-right">
                        {{ ucfirst(terbilang_id($payslip->gaji_bersih)) }} rupiah
                    </span>
                </div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                    <span class="font-semibold text-slate-500">Dicetak pada</span>
                    <span class="sm:text-right">
                        {{ $payslip->printed_at?->translatedFormat('d F Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection