@extends('layouts.app')
@section('title', 'Ledger – ' . ($inventory->name ?? 'Inventory'))

@section('content')
<section class="py-10 px-4">
    @php
    $fmtQty = function ($v) {
    if ($v === null) return '';
    $s = number_format((float)$v, 4, '.', ''); // e.g. 200.0000
    $s = rtrim(rtrim($s, '0'), '.'); // -> 200
    return $s;
    };
    $readOnly = (($role ?? null) === 'consultant');
    @endphp
    <div class="max-w-6xl mx-auto font-plus text-dec-none">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('sl.index') }}"
                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 shadow-sm hover:border-sky-300 hover:text-slate-700 mb-3">
                    ← Back to Inventory
                </a>

                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">
                    Stock Ledger
                </h1>
                <p class="mt-1 text-xs md:text-sm text-slate-500 max-w-xl">
                    Folder:&nbsp;
                    <span class="font-semibold text-slate-900">
                        {{ $inventory->name ?? 'Untitled inventory' }}
                    </span>
                    @if(!empty($inventory->description))
                    <span class="text-slate-400"> · {{ $inventory->description }}</span>
                    @endif
                </p>
                <p class="mt-2 text-xs md:text-sm text-slate-500 max-w-xl">
                    Manage stock movements for this folder. Current stock is calculated as
                    <span class="font-semibold">Quantity in − Quantity out</span>.
                </p>
            </div>

            <div class="text-right text-[11px] text-slate-500">
                <p class="uppercase tracking-[0.18em] mb-1">Current Stock</p>
                <p class="text-lg font-semibold text-slate-900">
                    <span id="sl-header-stock">
                        {{ number_format($totalStock ?? 0, 0, '.', ',') }}
                    </span>
                    <span class="text-[11px] text-slate-500">pc</span>
                </p>
            </div>
        </div>

        {{-- Table controls --}}
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-800">
                Movements
            </h2>

            @unless($readOnly)
            <button
                id="sl-add-row"
                type="button"
                class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
                + Add row
            </button>
            @endunless
        </div>

        {{-- Meta holder for future AJAX (keeps IDs the same as before) --}}
        <div id="sl-table"
            data-store-url="{{ route('sl.rows.store', $inventory) }}"
            data-update-url-base="{{ route('sl.rows.update', ['inventory' => $inventory, 'entry' => '__ID__']) }}"
            data-delete-url-base="{{ route('sl.rows.destroy', ['inventory' => $inventory, 'entry' => '__ID__']) }}">
        </div>

        {{-- Attachments meta (URLs + role flag) --}}
        <div id="sl-attachments-meta"
            class="hidden"
            data-read-only="{{ $readOnly ? 1 : 0 }}"
            data-upload-url-base="{{ route('sl.attachments.store', ['inventory' => $inventory, 'entry' => '__ID__']) }}"
            data-list-url-base="{{ route('sl.attachments.index', ['inventory' => $inventory, 'entry' => '__ID__']) }}"
            data-download-all-url-base="{{ route('sl.attachments.downloadAll', ['inventory' => $inventory->id, 'entry' => '__ID__']) }}">
        </div>

        {{-- Card-style rows (no horizontal scroll) --}}
        <div id="sl-body" class="space-y-3">
            @foreach($rows as $row)
            <div class="sl-row rounded-2xl border border-slate-200 bg-white/95 shadow-sm px-3 py-4 md:px-4 md:py-5 space-y-3"
                data-id="{{ $row->id }}"
                data-dirty="0">

                {{-- SUMMARY BAR: index + item + qtys + current stock --}}
                <div class="sl-summary flex items-center justify-between gap-3">
                    {{-- Left side: index + item code + qtys --}}
                    <div class="sl-summary-main flex-1 flex flex-wrap items-center gap-3 text-[11px] text-slate-500 cursor-pointer">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-white text-[10px]"
                                data-col="no">
                            </span>
                        </div>

                        {{-- Item code --}}
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">•</span>
                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">
                                    Item code
                                </label>
                                <input type="text"
                                    data-field="item"
                                    value="{{ $row->item }}"
                                    class="w-[220px] md:w-[260px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                           text-[11px] text-slate-800 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                                    @if($readOnly) disabled @endif>
                            </div>
                        </div>

                        {{-- Qty in / Qty out / Current --}}
                        <div class="flex flex-wrap items-center gap-3 ml-auto">
                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">
                                    Qty in
                                </label>
                                <input
                                    type="number"
                                    data-field="qty_in"
                                    value="{{ $fmtQty($row->qty_in) }}"
                                    min="0"
                                    step="1"
                                    class="w-[120px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                           text-[11px] text-right focus:border-sky-400 focus:bg-white
                           focus:outline-none focus:ring-1 focus:ring-sky-400"
                                    @if($readOnly) disabled @endif>
                            </div>

                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">
                                    Qty out
                                </label>
                                <input
                                    type="number"
                                    data-field="qty_out"
                                    value="{{ $fmtQty($row->qty_out) }}"
                                    min="0"
                                    step="1"
                                    class="w-[120px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                           text-[11px] text-right focus:border-sky-400 focus:bg-white
                           focus:outline-none focus:ring-1 focus:ring-sky-400"
                                    @if($readOnly) disabled @endif>
                            </div>

                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">
                                    Current
                                </label>
                                <input
                                    type="number"
                                    data-field="current_stock"
                                    value="{{ $fmtQty(max($row->qty_in - $row->qty_out, 0)) }}"
                                    readonly
                                    class="w-[120px] rounded-full border border-slate-100 bg-slate-50 px-3 py-1.5
                           text-[11px] text-right text-slate-700">
                            </div>
                        </div>

                        {{-- Chevron --}}
                        <span class="sl-chevron ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100
                     text-[9px] text-slate-500 transition-transform transition-colors duration-150">
                            ▾
                        </span>
                    </div>

                    {{-- Actions (not part of toggle area) --}}
                    <div class="inline-flex items-center gap-1 sl-actions">

                        @unless($readOnly)
                        {{-- Save + Delete --}}
                        <button type="button"
                            class="sl-save hidden rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-semibold text-white shadow-sm hover:bg-emerald-600">
                            <span class="sl-save-label">Save</span>
                            <span class="sl-save-spinner hidden ml-1 inline-block h-3 w-3 border-2 border-white/60 border-t-transparent rounded-full align-middle animate-spin"></span>
                        </button>
                        <button type="button"
                            class="sl-delete rounded-full bg-rose-50 px-3 py-1 text-[10px] font-semibold text-rose-600 border border-rose-100 hover:bg-rose-100">
                            Delete
                        </button>
                        @endunless

                        {{-- Attachment actions – Document Hub style --}}
                        <div class="flex items-center gap-2 ml-1 att-actions" data-row-id="{{ $row->id }}">
                            {{-- Optional small counter (starts at 0, JS will update) --}}
                            <span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600"
                                data-files-count>0</span>

                            @unless($readOnly)
                            {{-- Upload --}}
                            <button
                                type="button"
                                title="Upload attachments"
                                class="slatt-upload-bt inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                data-modal="att-upload"
                                data-row-id="{{ $row->id }}"
                                data-upload-url="{{ route('sl.attachments.store', ['inventory' => $inventory, 'entry' => $row]) }}"
                                data-list-url="{{ route('sl.attachments.index', ['inventory' => $inventory, 'entry' => $row]) }}">
                                {{-- upload icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.5 15.75v2.25A2.25 2.25 0 006.75 20.25h10.5A2.25 2.25 0 0019.5 18v-2.25M12 4.5v11.25m0 0l-3.75-3.75M12 15.75l3.75-3.75" />
                                </svg>
                            </button>
                            @endunless

                            {{-- View --}}
                            <button
                                type="button"
                                title="View attachments"
                                class="slatt-view-btn inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                data-modal="att-view"
                                data-row-id="{{ $row->id }}"
                                data-list-url="{{ route('sl.attachments.index', ['inventory' => $inventory, 'entry' => $row]) }}"
                                data-download-all-url="">
                                {{-- eye icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                    <circle cx="12" cy="12" r="3.25" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- DETAILS (collapsed by default) --}}
                <div class="sl-details mt-3 space-y-6 text-[11px] hidden">
                    {{-- Row: description + unit price --}}
                    <div class="flex flex-wrap gap-10">
                        {{-- Description --}}
                        <div class="w-full md:w-[420px]">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Description
                            </label>
                            <textarea
                                data-field="description"
                                class="w-[300px] md:w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2
                           text-[11px] resize-y min-h-[2.75rem]
                           focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>{{ $row->description }}</textarea>
                        </div>

                        {{-- Unit price --}}
                        <div class="w-full md:w-[340px]">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Unit price (exc. PPN)
                            </label>
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-slate-400">IDR</span>
                                <input type="text"
                                    data-field="unit_price"
                                    value="{{ number_format($row->unit_price, 0, '.', ',') }}"
                                    class="w-[280px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                                  text-[11px] text-right focus:border-sky-400 focus:bg-white
                                  focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>
                            </div>
                        </div>
                    </div>

                    {{-- Row: vendor + sales channel --}}
                    <div class="flex flex-wrap gap-10">
                        <div class="w-full md:w-[340px]">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Vendor
                            </label>
                            <input type="text"
                                data-field="vendor"
                                value="{{ $row->vendor }}"
                                class="w-[300px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                              text-[11px] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>
                        </div>

                        <div class="w-full md:w-[340px]">
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Sales channel
                            </label>
                            <input type="text"
                                data-field="sales_channel"
                                value="{{ $row->sales_channel }}"
                                class="w-[300px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                              text-[11px] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>
                        </div>
                    </div>

                    {{-- Row: dates + unit + restock --}}
                    <div class="grid gap-4 md:grid-cols-4">
                        {{-- Date in --}}
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Date in (Received)
                            </label>
                            <input
                                type="date"
                                data-field="date_in"
                                value="{{ optional($row->date_in)->format('Y-m-d') }}"
                                class="w-full max-w-[220px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                           text-[11px] text-center focus:border-sky-400 focus:bg-white
                           focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>
                        </div>

                        {{-- Date out --}}
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Date out (Sale)
                            </label>
                            <input
                                type="date"
                                data-field="date_out"
                                value="{{ optional($row->date_out)->format('Y-m-d') }}"
                                class="w-full max-w-[220px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5
                           text-[11px] text-center focus:border-sky-400 focus:bg-white
                           focus:outline-none focus:ring-1 focus:ring-sky-400" @if($readOnly) disabled @endif>
                        </div>

                        {{-- Unit toggle --}}
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Unit
                            </label>
                            <div class="inline-flex rounded-full bg-slate-100 p-0.5 text-[11px]" role="group">
                                {{-- kg --}}
                                <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium
                                   {{ $row->unit === 'kg' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                                    <input type="radio"
                                        class="sr-only"
                                        name="unit_{{ $row->id }}"
                                        data-field="unit"
                                        value="kg"
                                        {{ $row->unit === 'kg' ? 'checked' : '' }} @if($readOnly) disabled @endif>
                                    <span>kg</span>
                                </label>

                                {{-- pc --}}
                                <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium
                                   {{ $row->unit === 'pc' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                                    <input type="radio"
                                        class="sr-only"
                                        name="unit_{{ $row->id }}"
                                        data-field="unit"
                                        value="pc"
                                        {{ $row->unit === 'pc' ? 'checked' : '' }} @if($readOnly) disabled @endif>
                                    <span>pc</span>
                                </label>
                            </div>
                        </div>

                        {{-- Restock toggle --}}
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-1">
                                Restock
                            </label>
                            <div class="inline-flex rounded-full bg-slate-100 p-0.5 text-[11px]" role="group">
                                {{-- No --}}
                                <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium
                                   {{ $row->restock === 'no' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                                    <input type="radio"
                                        class="sr-only"
                                        name="restock_{{ $row->id }}"
                                        data-field="restock"
                                        value="no"
                                        {{ $row->restock === 'no' ? 'checked' : '' }} @if($readOnly) disabled @endif>
                                    <span>No</span>
                                </label>

                                {{-- Yes --}}
                                <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium
                                   {{ $row->restock === 'yes' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                                    <input type="radio"
                                        class="sr-only"
                                        name="restock_{{ $row->id }}"
                                        data-field="restock"
                                        value="yes"
                                        {{ $row->restock === 'yes' ? 'checked' : '' }} @if($readOnly) disabled @endif>
                                    <span>Yes</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Footer total (once for the whole sheet) --}}
        <div class="mt-4 flex items-center justify-end text-[11px] font-medium text-slate-700">
            <span class="mr-2">Total current stock:</span>
            <span id="sl-total-stock" class="font-semibold text-slate-900">0</span>
        </div>
    </div>
</section>

{{-- Upload modal (Document Hub style) -------------------------------------- --}}
<div
    id="att-upload-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="w-full max-w-3xl rounded-2xl bg-slate-950 text-slate-100 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-sm font-semibold">Upload Attachments</h3>
            <button
                type="button"
                title="Close"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-100 hover:bg-white/20">
                ✕
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <p class="font-plus text-xs text-slate-400">
                PDF and images only, max 20MB.
            </p>

            <div
                id="att-drop-zone"
                class="border border-dashed border-slate-700 rounded-xl bg-slate-900/70 px-6 py-10 text-center text-xs text-slate-400">
                <p class="font-plus mb-3 font-medium text-slate-200">
                    Drag &amp; drop files here
                </p>
                <p class="font-plus">or</p>
                <label
                    title="Select files from your computer"
                    class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-900 hover:bg-white">
                    Browse files
                    <input id="att-upload-input" type="file" class="hidden">
                </label>
            </div>

            <div id="att-upload-list" class="space-y-2 text-xs">
                {{-- JS: show pending files here --}}
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800">
                <h4 class="mb-2 text-xs font-semibold text-slate-200">
                    Existing attachments
                </h4>
                <div id="att-existing-list" class="space-y-2 text-xs">
                    {{-- JS: existing files with delete --}}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-800 px-6 py-4">
            <button
                type="button"
                class="att-modal-close rounded-full border border-slate-600 bg-slate-900 px-4 py-2 text-xs font-semibold text-slate-100 hover:bg-slate-800">
                Cancel
            </button>
            <button
                type="button"
                id="att-upload-submit"
                class="rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white hover:bg-sky-600 disabled:opacity-60"
                data-upload-url=""
                data-row-id=""
                data-list-url="">
                Upload
            </button>
        </div>
    </div>
</div>

{{-- Viewer modal ----------------------------------------------------------- --}}
<div
    id="att-view-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-slate-950 text-slate-100 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-sm font-semibold">Attachments Viewer</h3>
            <button
                type="button"
                title="Close viewer"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-100 hover:bg-white/20">
                ✕
            </button>
        </div>

        <div class="flex h-[540px]">
            <div class="w-64 border-r border-slate-800 bg-slate-950/80 py-4 px-6 space-y-2 text-xs" id="att-file-list">
                {{-- JS: file buttons --}}
            </div>

            <div class="flex-1 bg-slate-900/80">
                <iframe
                    id="att-viewer-frame"
                    class="h-full w-full border-0"
                    src=""></iframe>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-800 px-6 py-3 text-xs">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="att-download-file"
                    title="Download selected file"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-600 bg-slate-900 px-3 py-1.5 font-semibold text-slate-100 hover:bg-slate-800"
                    data-download-url="">
                    Download
                </button>

                <button
                    type="button"
                    id="att-download-all"
                    title="Download all attachments as ZIP"
                    class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-3 py-1.5 font-semibold text-white hover:bg-sky-600"
                    data-download-url="">
                    Download All
                </button>
            </div>
        </div>
    </div>
</div>

@endsection