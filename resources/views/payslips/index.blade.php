@extends('layouts.app')

@section('title', 'Payslips')

@section('content')
<div class="font-plus sheet-wrap py-10 pay-page">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Header + metrics --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('dashboard') }}"
                    class="inline-flex cursor-pointer items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                    ← Back to dashboard
                </a>

                <h1 class="mt-3 text-2xl font-semibold text-slate-900">Payslips</h1>
                <p class="font-plus mt-1 text-sm text-slate-500">
                    Generate and review employee payslips with automatic BPJS &amp; tax calculation.
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <div
                        class="inline-flex flex-col rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs">
                        <span class="uppercase tracking-[0.16em] text-slate-400">Total Payslips</span>
                        <span class="text-sm font-semibold text-slate-900">
                            {{ method_exists($payslips, 'total') ? $payslips->total() : $payslips->count() }}
                        </span>
                    </div>
                    <div
                        class="inline-flex flex-col rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs">
                        <span class="uppercase tracking-[0.16em] text-slate-400">Last Generated</span>
                        @php $latest = $payslips->first(); @endphp
                        <span class="text-sm font-semibold text-slate-900">
                            {{ $latest?->printed_at?->format('d M Y') ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            @php $user = auth()->user(); @endphp

            @if($user && $user->role !== 'consultant')
                <button
                    id="btnCreatePayslip"
                    class="self-start cursor-pointer rounded-full bg-utsBlue px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-slate-700/20 transition hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-utsGold focus:ring-offset-2 focus:ring-offset-slate-50">
                    + Create Payslip
                </button>
            @endif
        </div>

        {{-- List / empty state --}}
        <div class="rounded-2xl bg-white/90 p-4 shadow-xl shadow-slate-200/80 ring-1 ring-slate-100">
            @if($payslips->count() === 0)
            <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                <h2 class="text-lg font-semibold text-slate-900">No payslips yet</h2>

                @if($user && $user->role !== 'consultant')
                    <p class="font-plus max-w-sm text-sm text-slate-500">
                        Click <span class="font-semibold">Create Payslip</span> to generate the first one.
                    </p>
                    <button
                        id="btnCreatePayslip-empty"
                        class="cursor-pointer rounded-full bg-utsBlue px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-slate-700/20 transition hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-utsGold focus:ring-offset-2 focus:ring-offset-slate-50">
                        + Create Payslip
                    </button>
                @else
                    <p class="max-w-sm text-sm text-slate-500">
                        No payslips available yet. Please contact the admin to generate them.
                    </p>
                @endif
            </div>
            @else
            <div class="flex flex-col gap-3">
                @foreach ($payslips as $payslip)
                <a href="{{ route('payslips.show', $payslip) }}"
                    class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-white/90 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-lg">
                    {{-- Avatar --}}
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-slate-900 to-slate-700 text-sm font-semibold text-white shadow-md">
                        {{ strtoupper(mb_substr($payslip->nama, 0, 1)) }}
                    </div>

                    {{-- Main --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-slate-900">
                                {{ $payslip->nama }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                IDR {{ number_format($payslip->gaji, 0, '.', ',') }}
                            </span>
                        </div>

                        <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="text-slate-500">
                                {{ $payslip->posisi_karyawan }}
                            </span>
                            <span class="flex flex-wrap gap-2 justify-end">
                                @if($payslip->npwp)
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[11px] text-slate-500 ring-1 ring-slate-100">
                                    NPWP: {{ $payslip->npwp }}
                                </span>
                                @endif
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] text-emerald-600 ring-1 ring-emerald-100">
                                    {{ $payslip->printed_at?->format('d M Y') ?? 'Not printed' }}
                                </span>
                            </span>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="hidden text-xs font-medium text-slate-500 sm:flex sm:flex-col sm:items-end">
                        <span>View</span>
                        <span class="text-lg leading-none">›</span>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $payslips->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Left-side full-height modal --}}
<div class="pay-modal" id="payslipModal">
    <div class="pay-modal-backdrop"></div>

    <div
        class="relative z-10 flex h-full w-full max-w-xl flex-col bg-white px-6 py-5 shadow-2xl shadow-slate-900/40 ring-1 ring-slate-100
               sm:rounded-tr-[2rem] sm:rounded-br-[2rem] sm:rounded-l-none">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Create Payslip</h2>
                <p class="font-plus mt-2 text-xs text-slate-500">
                    Enter basic employee data. All BPJS &amp; tax fields will be calculated automatically.
                </p>
            </div>

            <button
                type="button"
                data-close
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-base leading-none text-slate-500 shadow-sm hover:bg-slate-50">
                ×
            </button>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('payslips.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-2 text-sm max-w-64">
                    <label for="pay-nama" class="text-xs font-medium text-slate-600">Nama</label>
                    <input id="pay-nama" type="text" name="nama" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-utsGold focus:outline-none focus:ring-2 focus:ring-utsGold/70">
                </div>

                <div class="space-y-2 text-sm max-w-64">
                    <label for="pay-posisi" class="text-xs font-medium text-slate-600">Posisi Karyawan</label>
                    <input id="pay-posisi" type="text" name="posisi_karyawan" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-utsGold focus:outline-none focus:ring-2 focus:ring-utsGold/70">
                </div>

                <div class="space-y-2 text-sm max-w-64">
                    <label for="pay-npwp" class="text-xs font-medium text-slate-600">NPWP</label>
                    <input id="pay-npwp" type="text" name="npwp"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-utsGold focus:outline-none focus:ring-2 focus:ring-utsGold/70">
                </div>

                <div class="space-y-2 text-sm max-w-64">
                    <label for="pay-gaji" class="text-xs font-medium text-slate-600">Gaji (IDR)</label>
                    <input id="pay-gaji" type="number" name="gaji" min="0" step="1" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-utsGold focus:outline-none focus:ring-2 focus:ring-utsGold/70">
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-auto flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    data-close
                    class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-full bg-utsBlue px-6 py-2 text-xs font-semibold text-white shadow-md shadow-slate-700/30 hover:bg-slate-900">
                    Generate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection