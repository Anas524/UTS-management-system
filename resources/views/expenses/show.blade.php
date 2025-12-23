@extends('layouts.app')
@section('title','Expense Sheet')

@section('content')

@php
    $isConsultant = auth()->user()?->role === 'consultant';
    $isClosed     = (bool) $sheet->is_closed;
    $isReadOnly   = $isConsultant || $isClosed;
    $lock         = $isReadOnly ? 'disabled readonly class=locked-input' : '';

    $role = auth()->user()->role ?? 'user';
@endphp

<div class="bg-slate-50/80 py-8">
    <div class="mx-auto max-w-6xl px-4 font-plus text-dec-none">

        <div id="expense-root" data-readonly="{{ $isConsultant || $sheet->is_closed ? '1' : '0' }}" class="sheet-card relative rounded-2xl border border-slate-200 bg-white shadow-md px-5 py-6 md:px-7 md:py-7 space-y-5">

            {{-- HEADER --------------------------------------------------- --}}
            <div class="sheet-head flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-[10px] font-semibold tracking-[0.16em] text-slate-400 uppercase">
                        {{ $sheet->company_name }}
                    </p>
                    <h1 class="mt-1 text-lg font-semibold text-slate-900">
                        Expense Sheet
                    </h1>
                    <div class="mt-1 inline-flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                        <span>
                            Period:
                            <strong class="text-slate-800">
                                {{ strftime('%B', mktime(0,0,0,$sheet->period_month,1)) }}
                                {{ $sheet->period_year }}
                            </strong>
                        </span>

                        @if($sheet->is_closed)
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-medium text-rose-600 border border-rose-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Closed
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-medium text-emerald-600 border border-emerald-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Open
                            </span>
                        @endif
                    </div>
                </div>

                <div class="sheet-head-actions flex flex-wrap items-center gap-2 text-[11px]">
                    <a href="{{ route('expenses.export', $sheet) }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-full bg-zinc-900 px-4 py-1.5 font-semibold text-white shadow-sm hover:bg-zinc-700">
                        Download Excel
                    </a>
                    <a href="{{ route('expenses.index', ['year' => $sheet->period_year]) }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">
                        All Sheets
                    </a>
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-4 py-1.5 font-semibold text-slate-700 hover:bg-slate-100">
                        Home
                    </a>
                </div>
            </div>

            {{-- ALERTS --------------------------------------------------- --}}
            @if($sheet->is_closed && $role !== 'consultant')
                <div class="sheet-alert info flex items-center gap-2 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-[11px] text-slate-800">
                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" class="text-utsBlue">
                        <path d="M12 17v-5M12 7h.01" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" />
                    </svg>
                    <strong class="mr-1">Closed:</strong>
                    <span>This period is closed. Reopen the year to edit.</span>
                </div>
            @endif

            @if (session('status'))
                <div class="sheet-alert success rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="sheet-alert error rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-[11px] text-rose-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- META ROW ------------------------------------------------- --}}
            <div class="sheet-meta flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 px-3 py-2 text-[11px] text-slate-700">
                <div>
                    Beginning Balance:
                    <strong>{{ is_null($begin) ? '–' : 'IDR '.number_format($begin,0,',','.') }}</strong>
                    @can('update', $sheet)
                        <button class="mini-link ml-1 text-utsBlue hover:text-sky-500" data-modal-open="#modalBegin">Set</button>
                    @endcan
                </div>

                @if($isConsultant)
                    <div class="ml-auto text-slate-500">
                        Read-only mode: you can view, download, and export.
                    </div>
                @endif
            </div>

            {{-- TOOLBAR -------------------------------------------------- --}}
            <div class="sheet-toolbar relative z-30 flex flex-wrap items-center gap-3">
                @can('update', $sheet)
                    <button
                        class="sheet-btn sheet-btn-primary inline-flex items-center rounded-full bg-zinc-900 px-4 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-zinc-700"
                        data-modal-open="#modalAddRow">
                        + Add Row
                    </button>
                @endcan

                {{-- Sort dropdown aligned right --}}
                <form method="GET"
                    action="{{ route('expenses.show', $sheet) }}"
                    id="sortForm"
                    class="ml-auto">
                    <input type="hidden" name="order" id="orderInput" value="{{ $order }}">

                    <div id="sortDropdown"
                        class="sort-dropdown exp-sort-wrapper relative inline-block text-left">

                        <button type="button"
                                id="sortTrigger"
                                class="sort-trigger exp-sort-trigger inline-flex w-44 items-center justify-between gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-[11px] font-medium text-slate-700 shadow-sm">
                            <span id="sortLabel">
                                {{ $order === 'desc' ? 'Newest first' : 'Oldest first' }}
                            </span>
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100">
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-3 w-3 text-slate-500">
                                    <polyline points="6 9 12 15 18 9"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>

                        <ul class="sort-menu exp-sort-menu absolute right-0 z-20 mt-2 w-44 rounded-2xl border border-slate-200 bg-white/95 py-1 shadow-xl"
                            role="listbox"
                            aria-labelledby="sortTrigger">
                            <li role="option" data-value="desc"
                                class="sort-option exp-sort-option cursor-pointer px-3 py-1.5 text-[11px] {{ $order==='desc' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                Newest first
                            </li>
                            <li role="option" data-value="asc"
                                class="sort-option exp-sort-option cursor-pointer px-3 py-1.5 text-[11px] {{ $order==='asc' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                Oldest first
                            </li>
                        </ul>
                    </div>
                </form>
            </div>

            {{-- TABLE ---------------------------------------------------- --}}
            <div class="sheet-table-wrap relative z-10 mt-1 rounded-xl border border-slate-200 bg-white">
                <table class="sheet-table w-full text-left text-[11px] text-slate-800">
                    <thead class="bg-slate-100 text-[11px] text-slate-600">
                        <tr>
                            <th class="px-3 py-2 font-semibold">No</th>
                            <th class="px-3 py-2 font-semibold">Date</th>
                            <th class="px-3 py-2 font-semibold">Description</th>
                            <th class="px-3 py-2 font-semibold">Doc number</th>
                            <th class="px-3 py-2 text-right font-semibold">Debit</th>
                            <th class="px-3 py-2 text-right font-semibold">Credit</th>
                            <th class="px-3 py-2 text-right font-semibold">Amount</th>
                            <th class="px-3 py-2 font-semibold">Remarks</th>
                            <th class="px-3 py-2 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lock = $isConsultant ? 'disabled readonly class=locked-input' : ''; @endphp

                        @forelse ($rows as $i => $r)
                            <tr class="border-t border-slate-200/80">
                                <td class="px-3 py-1.5 align-top">{{ $i+1 }}</td>
                                <td class="px-3 py-1.5 align-top">
                                    <form method="POST" action="{{ route('expenses.rows.update', [$sheet,$r]) }}">
                                        @csrf @method('PATCH')
                                        <input type="date" name="date"
                                               value="{{ $r->date->format('Y-m-d') }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 align-top">
                                    <input type="text" name="description" value="{{ $r->description }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 align-top">
                                    <input type="text" name="doc_number" value="{{ $r->doc_number }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 text-right align-top">
                                    <input type="text" name="debit" class="currency-input"
                                           value="{{ is_null($r->debit) ? '' : 'IDR '.number_format($r->debit,0,',','.') }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 text-right align-top">
                                    <input type="text" name="credit" class="currency-input"
                                           value="{{ is_null($r->credit) ? '' : 'IDR '.number_format($r->credit,0,',','.') }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 text-right align-top">
                                    <input type="text" name="amount" class="currency-input"
                                           value="{{ is_null($r->amount) ? '' : 'IDR '.number_format($r->amount,0,',','.') }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 align-top">
                                    <input type="text" name="remarks" value="{{ $r->remarks }}" {!! $lock !!}>
                                </td>
                                <td class="px-3 py-1.5 text-right align-top">
                                    <div class="icon-actions inline-flex items-center justify-end gap-1.5">
                                        @can('update', $sheet)
                                            <button class="icon-btn icon-save" type="submit" title="Save" aria-label="Save">
                                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle>
                                                    <path d="M9 12l2 2 4-4"></path>
                                                </svg>
                                                <span class="sr-only">Save</span>
                                            </button>
                                        @endcan
                                    </form>

                                    @can('update', $sheet)
                                        <form method="POST"
                                              action="{{ route('expenses.rows.delete', [$sheet,$r]) }}"
                                              class="inline-form js-confirm"
                                              data-confirm="Delete this row?">
                                            @csrf @method('DELETE')
                                            <button class="icon-btn icon-del" type="submit" title="Delete" aria-label="Delete">
                                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                    <path d="M10 11v6"></path>
                                                    <path d="M14 11v6"></path>
                                                </svg>
                                                <span class="sr-only">Delete</span>
                                            </button>
                                        </form>
                                    @endcan

                                    <button type="button"
                                            class="icon-btn icon-clip js-attach-toggle"
                                            data-endpoint="{{ route('attachments.index', [$sheet, $r]) }}"
                                            data-upload-url="{{ route('attachments.store', [$sheet, $r]) }}"
                                            title="Attachments"
                                            aria-label="Attachments">
                                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                            <path d="M21.44 11.05l-8.49 8.49a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.05 17.28a2 2 0 01-2.83-2.83l8.13-8.13" />
                                        </svg>
                                    </button>

                                    <button type="button"
                                            class="icon-btn icon-view js-open-attachments"
                                            data-endpoint="{{ route('attachments.index', [$sheet, $r]) }}"
                                            data-bundle-url="{{ route('attachments.bundle', [$sheet, $r]) }}"
                                            title="View attachments"
                                            aria-label="View attachments">
                                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>

                                    {{-- existing attach-panel stays same --}}
                                    <div id="attach-{{ $r->id }}" class="attach-panel">
                                        {{-- ... your existing attachment panel markup ... --}}
                                    </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="empty px-3 py-4 text-center text-[11px] text-slate-500">
                                    No rows
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 text-[11px] text-slate-700">
                        <tr>
                            <th colspan="4" class="px-3 py-2 text-right font-semibold">Total</th>
                            <th class="px-3 py-2 text-right font-semibold">
                                IDR {{ number_format($totalDebit,0,',','.') }}
                            </th>
                            <th class="px-3 py-2 text-right font-semibold">
                                IDR {{ number_format($totalCredit,0,',','.') }}
                            </th>
                            <th class="px-3 py-2 text-right font-semibold">
                                IDR {{ number_format($totalAmount,0,',','.') }}
                            </th>
                            <th class="px-3 py-2"></th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- SUMMARY STRIP ------------------------------------------- --}}
            <div class="sheet-summary grid gap-4 pt-2 md:grid-cols-3">
                <div class="sum-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="sum-label text-[11px] text-slate-500">Beginning Balance</div>
                    <div class="sum-value mt-1 text-sm font-semibold text-slate-900">
                        {{ is_null($begin) ? '–' : 'IDR '.number_format($begin,0,',','.') }}
                    </div>
                </div>
                <div class="sum-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="sum-label text-[11px] text-slate-500">Mutation</div>
                    <div class="sum-value mt-1 text-sm font-semibold text-slate-900">
                        IDR {{ number_format($mutation,0,',','.') }}
                    </div>
                </div>
                <div class="sum-item rounded-xl border border-slate-200 bg-zinc-900 px-4 py-3 text-slate-50">
                    <div class="sum-label text-[11px] text-slate-200">Ending Balance</div>
                    <div class="sum-value mt-1 text-sm font-semibold text-slate-50"">
                        {{ is_null($ending) ? '–' : 'IDR '.number_format($ending,0,',','.') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- All the modals + scripts stay exactly the same as in your file below --}}

{{-- Set Beginning Balance Modal --}}
<div class="modal" id="modalBegin" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-card">
        <div class="modal-head">
            <h3>Set Beginning Balance</h3>
            <button class="modal-x" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="{{ route('expenses.updateBeginning', $sheet) }}" class="modal-body">
            @csrf @method('PATCH')
            <div class="field-row">
                <label>Beginning Balance</label>
                <input type="text" name="beginning_balance" class="currency-input"
                    value="{{ is_null($begin) ? '' : 'IDR '.number_format($begin,0,',','.') }}"
                    @if($isConsultant) disabled readonly @endif>
            </div>
            <div class="modal-actions">
                @can('update', $sheet)
                <button type="submit" class="sheet-btn sheet-btn-primary">Save</button>
                @endcan
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Row Modal --}}
@can('update', $sheet)
<div class="modal" id="modalAddRow" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-card">
        <div class="modal-head">
            <h3>Add Row</h3>
            <button class="modal-x" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="{{ route('expenses.rows.add', $sheet) }}" class="modal-body">
            @csrf
            <div class="field-row">
                <label>Date <span class="req">*</span></label>
                <input type="date" name="date" required>
            </div>
            <div class="field-row">
                <label>Description <span class="req">*</span></label>
                <input type="text" name="description" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="sheet-btn sheet-btn-primary">Add</button>
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Confirm Delete Modal (reusable) --}}
<div class="modal" id="modalConfirm" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-card">
        <div class="modal-head">
            <h3>Confirm</h3>
            <button class="modal-x" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="margin:0;">Are you sure?</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            <button type="button" class="sheet-btn sheet-btn-primary" id="confirmYes">Yes, delete</button>
        </div>
    </div>
</div>

{{-- Upload modal ---------------------------------------------------------- --}}
<div
    id="att-upload-modal"
    class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">

    <div class="w-full max-w-xl rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold text-slate-900">
                Upload attachments
            </h3>
            <button
                type="button"
                title="Close"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg viewBox="0 0 24 24" class="h-4 w-4" aria-hidden="true">
                    <path
                        d="M6 6l12 12M18 6L6 18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
                <span class="sr-only">Close</span>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4 space-y-4 text-xs text-slate-600">
            <p class="font-plus text-[11px] text-slate-500">
                PDF and images only, max 25MB each.
            </p>

            {{-- Drop zone --}}
            <div
                id="att-drop-zone"
                class="border border-dashed border-slate-300 rounded-2xl bg-slate-50/80 px-6 py-8 text-center transition hover:bg-slate-50">
                <p class="font-plus mb-1.5 text-[12px] font-semibold text-slate-800">
                    Drag &amp; drop files here
                </p>
                <p class="font-plus text-[11px] text-slate-500">
                    or
                </p>
                <label
                    title="Select files from your computer"
                    class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-full bg-slate-900 px-4 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-slate-800">
                    Browse files
                    <input id="att-upload-input" type="file" class="hidden" multiple>
                </label>
            </div>

            {{-- Pending files --}}
            <div id="att-upload-list" class="space-y-1.5 text-[11px]">
                {{-- JS: show pending files here --}}
            </div>

            {{-- Existing files --}}
            <div class="mt-3 pt-3 border-t border-slate-100">
                <h4 class="mb-1.5 text-[11px] font-semibold text-slate-700">
                    Existing attachments
                </h4>

                {{-- shown only when there are NO files --}}
                <p id="att-empty-msg" class="text-[11px] text-slate-400">
                    No attachments yet.
                </p>

                {{-- list filled by JS when there ARE files --}}
                <div id="att-existing-list" class="space-y-1.5 text-[11px] text-slate-600">
                    {{-- JS: existing files with delete --}}
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
            <button
                type="button"
                class="att-modal-close inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                Cancel
            </button>
            <button
                type="button"
                id="att-upload-submit"
                class="inline-flex items-center rounded-full bg-sky-500 px-4 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-sky-600 disabled:opacity-60"
                data-upload-url=""
                data-row-id="">
                Upload
            </button>
        </div>
    </div>
</div>

{{-- Viewer modal --------------------------------------------------------- --}}
<div
    id="att-view-modal"
    class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">

    <div class="flex w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white text-slate-900 shadow-2xl border border-slate-200">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h3 class="text-sm font-semibold">
                Attachments viewer
            </h3>
            <button
                type="button"
                title="Close viewer"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg viewBox="0 0 24 24" class="h-4 w-4" aria-hidden="true">
                    <path
                        d="M6 6l12 12M18 6L6 18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
                <span class="sr-only">Close</span>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex h-[540px]">
            {{-- File list --}}
            <div
                id="att-file-list"
                class="w-64 border-r border-slate-100 bg-slate-50/80 py-4 px-4 space-y-1.5 text-[11px] text-slate-700 overflow-y-auto">
                {{-- JS: file buttons --}}
            </div>

            {{-- Preview --}}
            <div class="flex-1 bg-slate-900/95">
                <iframe
                    id="att-viewer-frame"
                    class="h-full w-full border-0"
                    src=""></iframe>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-5 py-3 text-[11px]">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="att-download-file"
                    title="Download selected file"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100"
                    data-download-url="">
                    Download
                </button>

                <button
                    type="button"
                    id="att-download-all"
                    title="Download all attachments as ZIP"
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-500 px-3 py-1.5 font-semibold text-white shadow-sm hover:bg-sky-600"
                    data-download-url="">
                    Download all
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        const isReadOnly = $('#expense-root').data('readonly') === 1
                        || $('#expense-root').data('readonly') === '1';

        let formToSubmit = null;

        // ----------------- BASIC MODALS (Begin / Add Row / Confirm) -----------------
        $('[data-modal-open]').on('click', function () {
            var sel = $(this).data('modal-open');
            $(sel).addClass('open');
        });

        $('[data-modal-close]').on('click', function () {
            $(this).closest('.modal').removeClass('open');
        });

        // ----------------- ALERT AUTO HIDE -----------------------------------------
        $('.sheet-alert').each(function () {
            var $a = $(this);
            setTimeout(function () {
                $a.addClass('is-hiding');
            }, 3000);
            setTimeout(function () {
                $a.slideUp(180, function () {
                    $(this).remove();
                });
            }, 3400);
        });

        $(document).on('click', '.sheet-alert', function () {
            $(this).addClass('is-hiding').slideUp(150, function () {
                $(this).remove();
            });
        });

        // =====================================================================
        //  NEW UPLOAD + VIEW MODALS  (doc-hub style)
        // =====================================================================

        // --------- UPLOAD MODAL ---------------------------------------------
        const $uploadModal  = $('#att-upload-modal');
        const $uploadInput  = $('#att-upload-input');
        const $uploadList   = $('#att-upload-list');
        const $uploadExist  = $('#att-existing-list');
        const $uploadSubmit = $('#att-upload-submit');

        let currentUploadEndpoint = null;

        function openUploadModal(endpoint) {
            $uploadInput.val('');
            $uploadList.empty();
            $uploadExist.empty();

            const $emptyMsg = $('#att-empty-msg');
            $emptyMsg.hide(); // decide later

            if (endpoint) {
                $uploadExist.append(
                    '<p class="text-[11px] text-slate-400" id="att-existing-loading">Loading…</p>'
                );

                $.getJSON(endpoint)
                    .done(function (resp) {
                        $('#att-existing-loading').remove();

                        // resp is a plain array: [ {id, name, mime, size, delete_url, uploaded_at, ...}, ... ]
                        let list = Array.isArray(resp) ? resp : [];

                        if (!list.length) {
                            $emptyMsg.show();
                            return;
                        }

                        // we have attachments -> hide empty text
                        $emptyMsg.hide();

                        list.forEach(function (item) {
                            const sizeKB   = item.size ? Math.round(item.size / 1024) : 0;
                            const uploaded = item.uploaded_at || '';

                            let rowHtml =
                                '<div class="att-existing-row flex items-center justify-between rounded-xl bg-slate-900/80 px-3 py-2 text-xs text-slate-100 mb-1">' +
                                    '<div class="flex flex-col min-w-0">' +
                                        '<span class="truncate max-w-xs font-medium">' +
                                            (item.name || ('Attachment #' + item.id)) +
                                        '</span>' +
                                        (uploaded
                                            ? '<span class="text-[11px] text-slate-400">Uploaded ' + uploaded + '</span>'
                                            : ''
                                        ) +
                                        (sizeKB
                                            ? '<span class="text-[11px] text-slate-400">' + sizeKB + ' KB</span>'
                                            : ''
                                        ) +
                                    '</div>';

                            if (!isReadOnly && item.delete_url) {
                                rowHtml +=
                                    '<button type="button" ' +
                                        'class="att-existing-delete inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-500 hover:bg-rose-600" ' +
                                        'data-delete-url="' + item.delete_url + '">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                                            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />' +
                                        '</svg>' +
                                    '</button>';
                            }

                            rowHtml += '</div>';

                            $uploadExist.append(rowHtml);
                        });
                    })
                    .fail(function () {
                        $('#att-existing-loading').remove();
                        $emptyMsg.show().text('Could not load attachments.');
                    });
            } else {
                // no endpoint
                $emptyMsg.show();
            }

            $uploadModal.removeClass('hidden');
            $('body').addClass('overflow-hidden');
        }

        // clip icon -> open upload modal with existing files + set upload URL
        $(document).on('click', '.js-attach-toggle', function () {
            const endpoint  = $(this).data('endpoint') || $(this).data('attachments-endpoint');
            const uploadUrl = $(this).data('upload-url') || '';

            if (uploadUrl) {
                $uploadSubmit.data('upload-url', uploadUrl);
            }

            openUploadModal(endpoint);
        });

        function closeUploadModal() {
            $uploadModal.addClass('hidden');
            $('body').removeClass('overflow-hidden');
        }

        // close upload (X + Cancel)
        $(document).on('click', '#att-upload-modal .att-modal-close', closeUploadModal);

        // click backdrop closes
        $(document).on('click', '#att-upload-modal', function (e) {
            if (e.target === this) closeUploadModal();
        });

        // ESC closes upload
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && !$uploadModal.hasClass('hidden')) {
                closeUploadModal();
            }
        });

        // show selected files
        $uploadInput.on('change', function () {
            const files = Array.from(this.files || []);
            $uploadList.empty();

            if (!files.length) return;

            files.forEach(f => {
                $uploadList.append(
                    `<div class="flex items-center justify-between rounded-lg bg-white px-3 py-1.5">
                        <span class="truncate">${f.name}</span>
                        <span class="text-[10px] text-slate-400">${(f.size/1024).toFixed(1)} KB</span>
                    </div>`
                );
            });
        });

        // Upload files via AJAX (multi-files -> "files[]")
        $uploadSubmit.on('click', function () {
            const uploadUrl = $(this).data('upload-url') || '';
            const files = Array.from($uploadInput[0].files || []);

            if (!uploadUrl) {
                alert('Upload URL is missing.');
                return;
            }
            if (!files.length) {
                alert('Please choose at least one file.');
                return;
            }

            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            files.forEach(function (file, idx) {
                fd.append('files[' + idx + ']', file);
            });

            $uploadSubmit.prop('disabled', true).text('Uploading…');

            $.ajax({
                url: uploadUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false
            })
            .done(function () {
                // simple approach: reload to refresh icons + viewer counts
                window.location.reload();
            })
            .fail(function () {
                alert('Could not upload files.');
            })
            .always(function () {
                $uploadSubmit.prop('disabled', false).text('Upload');
            });
        });

        // --------- VIEWER MODAL --------------------------------------------
        const $viewModal   = $('#att-view-modal');
        const $fileList    = $('#att-file-list');
        const $viewerFrame = $('#att-viewer-frame');
        const $dlFileBtn   = $('#att-download-file');
        const $dlAllBtn    = $('#att-download-all');

        let viewState = {
            items: [],
            current: 0
        };

        function openViewModal() {
            $viewModal.removeClass('hidden');
            $('body').addClass('overflow-hidden');
        }

        function closeViewModal() {
            $viewModal.addClass('hidden');
            $('body').removeClass('overflow-hidden');
        }

        // close viewer (X)
        $(document).on('click', '#att-view-modal .att-modal-close', closeViewModal);

        // click backdrop closes
        $(document).on('click', '#att-view-modal', function (e) {
            if (e.target === this) closeViewModal();
        });

        // ESC closes viewer (sharing same keydown listener as upload)
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && !$viewModal.hasClass('hidden')) {
                closeViewModal();
            }
        });

        function showCurrentFile() {
            if (!viewState.items.length) return;

            const it = viewState.items[viewState.current];
            const src = it.view || it.url || it.download || '';

            $viewerFrame.attr('src', src);
            $dlFileBtn.attr('data-download-url', it.download || src || '');
        }

        function renderFileList() {
            $fileList.empty();

            if (!viewState.items.length) {
                $fileList.append(
                    '<p class="text-[11px] text-slate-500">No attachments.</p>'
                );
                $viewerFrame.attr('src', '');
                $dlFileBtn.attr('data-download-url', '');
                return;
            }

            viewState.items.forEach((it, idx) => {
                const active = idx === viewState.current;

                const $btn = $(`
                    <button type="button"
                            class="att-file-btn w-full text-left rounded-lg px-3 py-2 text-[11px]
                                   ${active ? 'bg-slate-900 text-white' : 'hover:bg-slate-200/80'}"
                            data-index="${idx}">
                        <div class="font-semibold truncate">
                            ${it.name || it.original_name || 'Attachment'}
                        </div>
                        <div class="text-[10px] ${active ? 'text-slate-200' : 'text-slate-500'}">
                            ${it.mime || it.type || ''}
                        </div>
                    </button>
                `);

                $fileList.append($btn);
            });

            showCurrentFile();
        }

        // click item in left list
        $(document).on('click', '.att-file-btn', function () {
            const idx = parseInt($(this).data('index'), 10) || 0;
            viewState.current = idx;
            renderFileList();
        });

        // download current file
        $dlFileBtn.on('click', function () {
            const url = $(this).data('download-url');
            if (url) window.open(url, '_blank');
        });

        // download all as ZIP/bundle
        $dlAllBtn.on('click', function () {
            const url = $(this).data('download-url');
            if (url) window.location.href = url;
        });

        function fetchAndOpen(endpoint, bundleUrl) {
            $.getJSON(endpoint)
                .done(function (list) {
                    viewState.items = Array.isArray(list) ? list : [];

                    if (!viewState.items.length) {
                        alert('No attachments found.');
                        return;
                    }

                    viewState.current = 0;
                    $dlAllBtn.attr('data-download-url', bundleUrl || '');
                    openViewModal();
                    renderFileList();
                })
                .fail(function () {
                    alert('Could not load attachments.');
                });
        }

        // eye icon -> open viewer
        $(document).on('click', '.js-open-attachments', function () {
            const endpoint  = $(this).data('endpoint');
            const bundleUrl = $(this).data('bundle-url') || '';
            if (!endpoint) return;
            fetchAndOpen(endpoint, bundleUrl);
        });

        // =====================================================================
        //  CONFIRM DELETE (unchanged)
        // =====================================================================
        $(document).on('submit', 'form.js-confirm', function (e) {
            e.preventDefault();
            formToSubmit = this;
            $('#confirmMessage').text($(this).data('confirm') || 'Are you sure?');
            openConfirmModal();
        });

        $('#confirmYes').on('click', function () {
            if (formToSubmit) {
                closeConfirmModal();
                formToSubmit.submit();
                formToSubmit = null;
            }
        });

        function openConfirmModal() {
            $('#modalConfirm').addClass('open');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmModal() {
            $('#modalConfirm').removeClass('open');
            document.body.style.overflow = '';
        }

        $(document).on('click', '#modalConfirm .modal-backdrop, #modalConfirm [data-modal-close]', closeConfirmModal);

        // =====================================================================
        //  CURRENCY FORMAT (unchanged)
        // =====================================================================
        function formatIDR(v) {
            v = (v || '').toString().replace(/[^0-9.-]/g, '');
            if (!v) return '';
            var n = parseInt(v, 10);
            if (isNaN(n)) return '';
            return 'IDR ' + n.toLocaleString('id-ID');
        }

        $(document).on('blur', '.currency-input', function () {
            $(this).val(formatIDR($(this).val()));
        }).on('focus', '.currency-input', function () {
            $(this).val($(this).val().replace(/IDR\s?/, '').replace(/\./g, ''));
        });

        $('form').on('submit', function () {
            $(this).find('.currency-input').each(function () {
                this.value = this.value.replace(/IDR\s?/, '').replace(/\./g, '');
            });
        });

        // =====================================================================
        //  SORT DROPDOWN (unchanged)
        // =====================================================================
        var $dd     = $('#sortDropdown');
        var $trigger = $('#sortTrigger');
        var $menu   = $dd.find('.sort-menu');
        var $label  = $('#sortLabel');
        var $input  = $('#orderInput');
        var $form   = $('#sortForm');

        function openDD() {
            $dd.addClass('open');
            $trigger.attr('aria-expanded', 'true');
        }

        function closeDD() {
            $dd.removeClass('open');
            $trigger.attr('aria-expanded', 'false');
        }

        $trigger.on('click', function (e) {
            e.stopPropagation();
            $dd.hasClass('open') ? closeDD() : openDD();
        });

        $menu.on('click', '.sort-option', function () {
            var $opt = $(this);
            $menu.find('.sort-option').removeClass('is-active');
            $opt.addClass('is-active');

            var val = $opt.data('value');
            $label.text($.trim($opt.text()));
            $input.val(val);

            closeDD();
            $form.trigger('submit');
        });

        $(document).on('click', function (e) {
            if (!$dd.is(e.target) && $dd.has(e.target).length === 0) {
                closeDD();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeDD();
        });

        // delete existing attachment from Upload modal
        $(document).on('click', '.att-existing-delete', function () {
            const url  = $(this).data('delete-url');
            if (!url) return;

            if (!confirm('Delete this attachment?')) return;

            const $row = $(this).closest('.att-existing-row');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                }
            })
            .done(function () {
                $row.remove();

                // if no more rows, show "No attachments yet."
                if (!$uploadExist.find('.att-existing-row').length) {
                    $uploadExist.html(
                        '<p class="text-[11px] text-slate-400">No attachments yet.</p>'
                    );
                }
            })
            .fail(function () {
                alert('Could not delete attachment.');
            });
        });
    });
</script>
@endpush
@endsection