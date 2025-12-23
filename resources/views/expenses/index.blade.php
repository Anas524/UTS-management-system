@extends('layouts.app')
@section('title','Expense Sheets')

@php
$isConsultant = auth()->user()?->role === 'consultant';
@endphp

@section('content')
<section class="px-4 py-10 bg-gradient-to-b from-slate-50 via-slate-100 to-slate-50">
    <div class="mx-auto max-w-6xl font-plus text-dec-none space-y-8">

        {{-- HERO / HEADER ---------------------------------------------------- --}}
        <div
            class="relative overflow-hidden rounded-3xl bg-slate-900 text-white shadow-2xl">

            {{-- subtle glow circles --}}
            <div class="pointer-events-none absolute -left-16 -top-16 h-40 w-40 rounded-full bg-sky-500/25 blur-2xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-0 h-52 w-52 rounded-full bg-utsGold/25 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 px-6 py-7 md:flex-row md:items-center md:justify-between md:px-10">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
                        <img src="{{ asset('images/UTS.png') }}" alt="UTS" class="h-10 w-10 object-contain">
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold tracking-[0.18em] text-slate-300 uppercase">
                            Finance • PT: Universal Trade Services
                        </p>
                        <h1 class="mt-1 text-2xl md:text-3xl font-semibold tracking-tight">
                            Expense Sheets
                        </h1>
                        <p class="mt-2 text-xs text-slate-200/90 max-w-xl">
                            @if($isConsultant)
                            View monthly expense sheets, attachments and exports shared with you.
                            @else
                            Create, manage and close monthly expense sheets for your finance workflow.
                            @endif
                        </p>
                    </div>
                </div>

                {{-- header actions --}}
                <div class="flex flex-col items-stretch gap-2 text-xs md:items-end">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-white text-slate-900 px-5 py-2 font-semibold shadow hover:bg-slate-100">
                        ← Back to Dashboard
                    </a>

                    @can('create', App\Models\ExpenseSheet::class)
                    <button
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-utsGold text-slate-900 px-5 py-2 font-semibold shadow hover:bg-yellow-400"
                        data-modal-open="#modalCreate">
                        + Add Sheet
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        {{-- YEAR TOOLBAR ----------------------------------------------------- --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs text-slate-500">
                <span class="inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 border border-slate-200">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Active year:
                    <strong class="text-slate-800">{{ $activeYear }}</strong>
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3">

                {{-- Year selector --}}
                @if($multiYear)
                <form method="GET" action="{{ route('expenses.index') }}">
                    <div id="year-dd" class="year-dd relative">
                        <input type="hidden" name="year" id="yearInput" value="{{ $activeYear }}">
                        <button type="button"
                            id="yearBtn"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            class="year-dd__button inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            <span id="yearLabel">{{ $activeYear }}</span>
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3 w-3">
                                <polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <ul id="yearList"
                            class="year-dd__list absolute right-0 z-20 mt-2 w-32 overflow-hidden rounded-2xl border border-slate-200 bg-white/95 py-1 text-[11px] shadow-lg"
                            role="listbox" tabindex="-1">
                            @foreach($yearOptions as $y)
                            <li role="option"
                                class="year-dd__option cursor-pointer px-3 py-1.5 text-slate-600 hover:bg-slate-50 {{ (int)$y === (int)$activeYear ? 'is-selected bg-slate-100 font-semibold text-slate-800' : '' }}"
                                aria-selected="{{ (int)$y === (int)$activeYear ? 'true' : 'false' }}"
                                data-value="{{ $y }}">
                                {{ $y }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </form>
                @else
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                    Year: {{ $activeYear }}
                </div>
                @endif

                {{-- Year actions (not for consultants) --}}
                @if(!$isConsultant)
                @if($hasOpen)
                <form method="POST" action="{{ route('expenses.year.close', $activeYear) }}">
                    @csrf
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100"
                        data-year-confirm="close"
                        data-year="{{ $activeYear }}">
                        Close {{ $activeYear }}
                    </button>
                </form>
                @endif

                {{-- OPEN NEXT YEAR (always) --}}
                <form method="POST" action="{{ route('expenses.year.openNext', $activeYear) }}">
                    @csrf
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-full bg-sky-500 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-sky-600"
                        data-year-confirm="open-next"
                        data-year="{{ $activeYear + 1 }}">
                        Open {{ $activeYear + 1 }}
                    </button>
                </form>

                {{-- REOPEN YEAR --}}
                @if($hasClosed && !$hasOpen)
                <form method="POST" action="{{ route('expenses.year.reopen', $activeYear) }}">
                    @csrf
                    <button type="button"
                        class="inline-flex items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100"
                        data-year-confirm="reopen"
                        data-year="{{ $activeYear }}">
                        Reopen {{ $activeYear }}
                    </button>
                </form>
                @endif
                @endif
            </div>
        </div>

        {{-- GLOBAL TOTALS ----------------------------------------------------- --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div
                class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm px-5 py-4">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                    Total Debit
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    IDR {{ number_format($allDebit, 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                    Sum of all visible sheets.
                </p>
            </div>

            <div
                class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm px-5 py-4">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                    Total Credit
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    IDR {{ number_format($allCredit, 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                    Sum of all visible sheets.
                </p>
            </div>
        </div>

        {{-- STATUS MESSAGE ---------------------------------------------------- --}}
        @if (session('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-700">
            {{ session('status') }}
        </div>
        @endif

        {{-- TABLE CARD -------------------------------------------------------- --}}
        <div
            class="overflow-hidden rounded-3xl bg-white/80 backdrop-blur border border-slate-200 shadow-md">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-900">
                    Sheets for {{ $activeYear }}
                </h2>
                <p class="text-[11px] text-slate-500">
                    {{ $sheets->count() }} sheet{{ $sheets->count() === 1 ? '' : 's' }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2">No</th>
                            <th class="px-4 py-2">Period</th>
                            <th class="px-4 py-2">Owner</th>
                            <th class="px-4 py-2 text-right">Total Debit</th>
                            <th class="px-4 py-2 text-right">Total Credit</th>
                            <th class="px-4 py-2">Created</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sheets as $i => $s)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-2 align-top">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 align-top">
                                {{ strftime('%B', mktime(0,0,0,$s->period_month,1)) }} {{ $s->period_year }}
                            </td>
                            <td class="px-4 py-2 align-top">{{ $s->user->name }}</td>
                            <td class="px-4 py-2 align-top text-right">
                                IDR {{ number_format((int)$s->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 align-top text-right">
                                IDR {{ number_format((int)$s->total_credit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 align-top">
                                {{ $s->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-2 align-top">
                                @if($s->is_closed)
                                <a href="{{ route('expenses.show', $s) }}"
                                    title="Closed sheet"
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">
                                    View (Closed)
                                </a>
                                @else
                                <a href="{{ route('expenses.show', $s) }}"
                                    class="inline-flex items-center rounded-full bg-sky-500 px-3 py-1 text-[11px] font-semibold text-white hover:bg-sky-600">
                                    Open
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-[11px] text-slate-500">
                                No sheets yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

{{-- CREATE SHEET MODAL ------------------------------------------------------ --}}
@can('create', App\Models\ExpenseSheet::class)
<div class="modal" id="modalCreate" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="font-plus modal-card expense-modal-card max-w-sm rounded-3xl bg-white border border-slate-200 shadow-2xl">
        <div class="modal-head flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Create Expense Sheet</h3>
            <button class="modal-x text-lg leading-none text-slate-400 hover:text-slate-700" data-modal-close>&times;</button>
        </div>

        <form method="POST" action="{{ route('expenses.store') }}" class="modal-body px-5 py-4 space-y-4">
            @csrf
            @php
            $activeMonth = old('period_month', 1); // 1 = January
            $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];
            @endphp

            <div class="field-row space-y-1.5">
                <label class="text-[11px] font-medium text-slate-700">Period Month</label>

                {{-- real value --}}
                <input type="hidden" name="period_month" id="monthSpinnerInput" value="{{ $activeMonth }}">

                {{-- spinner UI --}}
                <div id="monthSpinner"
                    class="month-spinner flex w-full items-center rounded-xl border border-slate-200
            bg-white/90 px-3 py-1.5 text-[12px] text-slate-800 shadow-sm hover:bg-slate-50
            cursor-default select-none">
                    {{-- label --}}
                    <span id="monthSpinnerLabel" class="flex-1 font-medium truncate">
                        {{ $monthNames[$activeMonth] ?? 'January' }}
                    </span>

                    {{-- up / down arrows using your SVG, no border --}}
                    <div class="flex flex-col ml-1">
                        <button type="button"
                            id="monthSpinnerUp"
                            class="month-arrow flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16" height="16" viewBox="0 0 16 16" fill="currentColor"
                                class="h-3 w-3">
                                <path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                            </svg>
                        </button>

                        <button type="button
                " id="monthSpinnerDown"
                            class="month-arrow flex items-center justify-center">
                            {{-- same path, rotated for down --}}
                            <svg xmlns="http://www.w3.org/2000/svg"
                                width="16" height="16" viewBox="0 0 16 16" fill="currentColor"
                                class="h-3 w-3 rotate-180">
                                <path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="field-row space-y-1.5">
                <label class="text-[11px] font-medium text-slate-700">Period Year</label>
                <input type="number"
                    name="period_year"
                    min="2000"
                    max="2100"
                    value="{{ (int)request('year', $activeYear) }}"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-[12px] text-slate-800 focus:border-utsGold focus:ring-2 focus:ring-utsGold/40">
            </div>

            <div class="modal-actions flex items-center justify-end gap-2 pt-4">
                <button type="button"
                    class="inline-flex items-center rounded-full border border-slate-200 bg-white
                   px-4 py-1.5 text-[11px] font-semibold text-slate-600
                   hover:bg-slate-50 hover:border-slate-300 transition"
                    data-modal-close>
                    Cancel
                </button>

                <button type="submit"
                    class="inline-flex items-center rounded-full bg-gradient-to-r from-sky-500 to-blue-500
                   px-5 py-1.5 text-[11px] font-semibold text-white
                   shadow-md shadow-sky-200 hover:from-sky-600 hover:to-blue-600
                   focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-1
                   transition">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Year confirm modal (Close / Open / Reopen) --}}
<div class="modal" id="yearConfirmModal" aria-hidden="true">
    <div class="modal-backdrop bg-slate-900/40 backdrop-blur-sm" data-year-confirm-close></div>

    <div class="font-plus modal-card max-w-sm rounded-3xl bg-white border border-slate-200
                shadow-[0_24px_60px_rgba(15,23,42,0.35)] overflow-hidden">

        {{-- header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Confirm action</h3>

            <button type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-full
                           border border-slate-200 text-[10px] text-slate-500
                           hover:bg-slate-100 hover:text-slate-700"
                data-year-confirm-close>
                <span class="sr-only">Close</span>
                ✕
            </button>
        </div>

        {{-- body --}}
        <div class="px-5 pb-4">
            <p id="yearConfirmMessage" class="text-[12px] text-slate-700 leading-relaxed">
                Are you sure?
            </p>
        </div>

        {{-- footer --}}
        <div class="flex items-center justify-end gap-2 px-5 pb-4">
            <button type="button"
                id="yearConfirmCancel"
                class="inline-flex items-center rounded-full border border-slate-200
                           bg-white px-4 py-1.5 text-[11px] font-semibold text-slate-600
                           hover:bg-slate-50"
                data-year-confirm-close>
                Cancel
            </button>
            <button type="button"
                id="yearConfirmOk"
                class="inline-flex items-center rounded-full bg-sky-500 px-4 py-1.5
                           text-[11px] font-semibold text-white hover:bg-sky-600">
                Yes, continue
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function() {
        // open modal
        $('[data-modal-open]').on('click', function() {
            var sel = $(this).data('modal-open');
            $(sel).addClass('open');
        });

        // close modal (button or backdrop)
        $('[data-modal-close]').on('click', function() {
            $(this).closest('.modal').removeClass('open');
        });

        // auto-open Create modal if ?new=1 is in URL
        var q = window.location.search.replace(/^\?/, '').split('&')
            .reduce(function(acc, kv) {
                if (!kv) return acc;
                var p = kv.split('=');
                acc[decodeURIComponent(p[0])] = decodeURIComponent((p[1] || '').replace(/\+/g, ' '));
                return acc;
            }, {});
        if (q.new === '1') {
            $('#modalCreate').addClass('open');
        }

        // ----- Simple Year dropdown (Expense index) -----
        var $yearBtn = $('#yearBtn');
        var $yearList = $('#yearList');
        var $yearInput = $('#yearInput');
        var $yearLabel = $('#yearLabel');

        if ($yearBtn.length && $yearList.length && $yearInput.length && $yearLabel.length) {
            $yearList.hide();

            $yearBtn.on('click', function(e) {
                e.stopPropagation();
                $yearList.toggle();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#year-dd').length) {
                    $yearList.hide();
                }
            });

            $yearList.on('click', '.year-dd__option', function() {
                var val = $(this).data('value');
                $yearInput.val(val);
                $yearLabel.text(val);
                $yearList.hide();
                $('#year-dd').closest('form')[0].submit();
            });
        }

        // ----- Year confirm modal (Close/Open/Reopen) ----------------------
        var pendingYearForm = null;

        $('[data-year-confirm]').on('click', function(e) {
            e.preventDefault();

            pendingYearForm = $(this).closest('form');

            var action = $(this).data('year-confirm');
            var year = $(this).data('year');
            var msg = '';

            if (action === 'close') {
                msg = 'Close ' + year + '? You will not be able to edit unless you reopen.';
            } else if (action === 'open-next') {
                msg = 'Open ' + year + ' and create sheets if missing?';
            } else if (action === 'reopen') {
                msg = 'Reopen ' + year + ' for editing?';
            } else {
                msg = 'Are you sure you want to continue?';
            }

            $('#yearConfirmMessage').text(msg);
            $('#yearConfirmModal').addClass('open');
        });

        $('#yearConfirmOk').on('click', function() {
            if (pendingYearForm) {
                pendingYearForm.submit();
            }
        });

        $('[data-year-confirm-close]').on('click', function() {
            $('#yearConfirmModal').removeClass('open');
            pendingYearForm = null;
        });

        // ----- Month spinner (Create modal) ----------------------------
        var MONTHS = [
            'January', 'February', 'March', 'April',
            'May', 'June', 'July', 'August',
            'September', 'October', 'November', 'December'
        ];

        var $mInput = $('#monthSpinnerInput');
        var $mLabel = $('#monthSpinnerLabel');
        var $mUp = $('#monthSpinnerUp');
        var $mDown = $('#monthSpinnerDown');
        var $mSpin = $('#monthSpinner');

        function getMonthIndex() {
            var val = parseInt($mInput.val(), 10);
            if (isNaN(val) || val < 1 || val > 12) val = 1;
            return val;
        }

        function setMonthIndex(idx) {
            if (idx < 1) idx = 12;
            if (idx > 12) idx = 1;
            $mInput.val(idx);
            $mLabel.text(MONTHS[idx - 1]);
        }

        if ($mSpin.length) {
            // init label
            setMonthIndex(getMonthIndex());

            $mUp.on('click', function() {
                setMonthIndex(getMonthIndex() + 1);
            });

            $mDown.on('click', function() {
                setMonthIndex(getMonthIndex() - 1);
            });

            // scroll wheel over the spinner
            $mSpin.on('wheel', function(e) {
                e.preventDefault();
                if (e.originalEvent.deltaY < 0) {
                    setMonthIndex(getMonthIndex() + 1);
                } else {
                    setMonthIndex(getMonthIndex() - 1);
                }
            });

            // keyboard arrows when focused
            $mSpin.on('keydown', function(e) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    setMonthIndex(getMonthIndex() + 1);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    setMonthIndex(getMonthIndex() - 1);
                }
            });
        }

    });
</script>
@endpush
@endsection