@extends('layouts.app')
@section('title', 'Purchase Order #'.$po->po_number)

@php
$isConsultant = auth()->user()?->role === 'consultant';
$isClosedYear = (bool) $po->is_closed;

$canUpdate = auth()->user()?->can('update', $po);

// helper: format IDR integer
$fmtIDR = fn($n) => 'IDR ' . number_format((int) round($n), 0, ',', '.');

// All rows
$rows = $po->rows ?? collect();

// Line total: use the stored "amount" column directly
$lineAmount = function ($r) {
return (int) round((float) ($r->amount ?? 0));
};

// Subtotal = sum of "amount"
$subtotalIDR = $rows->sum(fn ($r) => $lineAmount($r));

// ---- IMPORTANT: use SAVED values for calculations ----
$kindSaved = strtolower($po->tax_kind ?? 'ppn');

/**
* Some POs still have the real tax in tax_value_idr,
* newer ones use ppn_rate as the absolute IDR amount.
* Prefer tax_value_idr when it exists, otherwise ppn_rate.
*/
$rawTax = $po->tax_value_idr ?? $po->ppn_rate ?? 0;

$manualSaved = (int) round((float) $rawTax);
$taxIDR = ($kindSaved === 'none') ? 0 : max(0, $manualSaved);
$totalIDR = (int) $subtotalIDR + (int) $taxIDR;

// For UI controls we keep last-typed values
$kindUi = strtolower(old('tax_kind', $po->tax_kind ?? 'ppn'));
$rateUi = old('ppn_rate', $rawTax); // use same rawTax for the input

$taxKindText = $kindSaved === 'pph'
? 'PAJAK PENGHASILAN (PPH)'
: ($kindSaved === 'none' ? 'NO TAX' : 'PAJAK PERTAMBAHAN NILAI (PPN)');

$trim4 = function ($n) {
$f = number_format((float) $n, 4, '.', '');
return rtrim(rtrim($f, '0'), '.');
};

/** "34,111.765" / trims trailing .00xx nicely */
$fmtMoney = fn ($n) => \App\Support\Num::fmtMoney((string) $n, prefix: 'IDR ');

// readonly flags
$ro = $isConsultant || $isClosedYear; // also readonly when year closed
$roAttr = $ro ? 'disabled readonly' : '';
$roClass = $ro ? ' locked-input' : '';
$canEdit = !$ro && auth()->user()?->can('update', $po);
@endphp

@section('content')

<div class="sheet-wrap" data-readonly="{{ ($isConsultant || $isClosedYear) ? 'true' : 'false' }}">
    <div class="sheet-card">
        <div class="sheet-head">
            <div>
                <div class="sheet-company">PT: Universal Trade Services</div>
                <h1 class="sheet-title">Purchase Order</h1>
                <div class="text-sm text-muted">Created: {{ $po->created_at?->format('Y-m-d H:i') }}</div>
            </div>

            <div class="sheet-head-actions">
                <a href="{{ route('po.index') }}" class="sheet-btn sheet-btn-outline">← Back to list</a>
                <a href="{{ route('po.pdf', $po) }}" class="sheet-btn sheet-btn-ghost">Export PDF</a>

                {{-- Attachments unified button --}}
                <div class="att-actions">
                    <button
                        type="button"
                        class="sheet-btn sheet-btn-ghost att-trigger"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        data-index-url="{{ route('po.attachments.index', $po) }}"
                        data-upload-url="{{ route('po.attachments.store', $po) }}"
                        data-endpoint="{{ route('po.attachments.index', $po) }}"
                        data-bundle-url="{{ route('po.attachments.bundle', $po) }}"
                        data-csrf="{{ csrf_token() }}"
                        data-initial-count="{{ $po->attachments_count ?? 0 }}">
                        Attachments
                        <span class="att-badge" id="poatt-count">{{ $po->attachments_count ?? 0 }}</span>
                        <svg class="att-caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                            <path d="M5 7l5 6 5-6" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </button>

                    <div class="att-menu" role="menu" aria-label="Attachments menu">
                        @if($canEdit)
                        <button class="att-item js-att-manage" role="menuitem">Manage uploads</button>
                        @endif
                        <button class="att-item js-att-view" role="menuitem">View attachments</button>
                    </div>
                </div>

                @if($canEdit)
                <button type="submit" form="poHdrForm" class="sheet-btn sheet-btn-primary">
                    Save PO
                </button>
                @endif
            </div>

            @if($isConsultant)
            <div class="sheet-head-note">Read-only mode: you can view and download.</div>
            @endif

            @php
            $role = auth()->user()->role ?? 'user';
            @endphp

            @if($isClosedYear && $role !== 'consultant')
            <div class="sheet-alert info" style="display:flex;align-items:center;gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 17v-5M12 7h.01"
                        stroke="#01305A"
                        stroke-width="2"
                        fill="none"
                        stroke-linecap="round" />
                </svg>
                <div>
                    <strong>Closed:</strong>
                    Year {{ optional($po->po_date)->format('Y') ?? '—' }} is closed for Purchase Orders.
                    Reopen the year to edit this PO.
                </div>
            </div>
            @endif
        </div>

        {{-- Header form (same view for read and edit) --}}
        <form method="POST" action="{{ route('po.update',$po) }}" id="poHdrForm" data-update-url="{{ route('po.update',$po) }}" data-csrf="{{ csrf_token() }}">
            @csrf @method('PATCH')
            <div class="admin-grid">
                <div class="field-row">
                    <label>PO Number</label>
                    <input name="po_number" class="po-input{{ $roClass }}" value="{{ old('po_number',$po->po_number) }}" {{ $roAttr }}>
                </div>
                <div class="field-row">
                    <label>Date</label>
                    <input type="date" name="po_date" class="po-input{{ $roClass }}" value="{{ old('po_date', $po->po_date_for_input) }}" {{ $roAttr }}>
                </div>
                <div class="field-row" style="grid-column:1/-1;">
                    <label>Address</label>
                    <input name="address" class="po-input{{ $roClass }}" value="{{ old('address',$po->address) }}" {{ $roAttr }}>
                </div>
            </div>

            @php
            $statusVal = old('status', $po->status ?? 'open');
            $statusLabel = ucfirst(str_replace('_', ' ', $statusVal));
            @endphp
            <div class="field-row field-row--status">
                <label>Status</label>

                @if($canEdit)
                <div class="status-actions">
                    <input type="hidden" name="status" id="po-status" value="{{ $statusVal }}">

                    <button type="button" class="status-trigger" aria-haspopup="menu" aria-expanded="false">
                        <span id="po-status-label">{{ $statusLabel }}</span>
                        <svg class="status-caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                            <path d="M5 7l5 6 5-6" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </button>

                    <div class="status-menu" role="menu" aria-label="Status menu">
                        <button class="status-item" role="menuitem" data-val="open">Open</button>
                        <button class="status-item" role="menuitem" data-val="closed">Closed</button>
                        <button class="status-item" role="menuitem" data-val="awaiting_response">Awaiting Response</button>
                        <button class="status-item" role="menuitem" data-val="transferred">Transferred</button>
                    </div>
                </div>
                @else
                <div class="readflat">{{ ucfirst(str_replace('_',' ',$po->status ?? 'open')) }}</div>
                @endif
            </div>

            {{-- Supplier & Terms --}}
            <div class="po-info-grid">
                {{-- Supplier (left) --}}
                <div class="po-box">
                    <div class="po-box-title">Supplier Information</div>
                    <div class="po-box-grid">
                        <label>Company Name
                            <input type="text" name="sup_company" class="po-input{{ $roClass }}" value="{{ old('sup_company',$po->sup_company) }}" form="poHdrForm" {{ $roAttr }}>
                        </label>
                        <label>Company Address
                            <textarea name="sup_address" rows="2" class="po-input{{ $roClass }}" form="poHdrForm" {{ $roAttr }}>{{ old('sup_address',$po->sup_address) }}</textarea>
                        </label>
                        <label>Phone Number
                            <input type="text" name="sup_phone" class="po-input{{ $roClass }}" value="{{ old('sup_phone',$po->sup_phone) }}" form="poHdrForm" {{ $roAttr }}>
                        </label>
                        <label>E-mail
                            <input type="email" name="sup_email" class="po-input{{ $roClass }}" value="{{ old('sup_email',$po->sup_email) }}" form="poHdrForm" {{ $roAttr }}>
                        </label>
                        <label>NPWP
                            <input type="text" name="sup_npwp" class="po-input{{ $roClass }}" value="{{ old('sup_npwp',$po->sup_npwp) }}" form="poHdrForm" {{ $roAttr }}>
                        </label>
                    </div>
                </div>

                <div class="po-box">
                    <div class="po-box-title">Ship To (PT. UNIVERSAL TRADE SERVICES)</div>
                    <div class="po-box-grid">
                        <label>Recipient
                            <input type="text" name="ship_to_recipient" class="po-input{{ $roClass }}" form="poHdrForm" value="{{ old('ship_to_recipient', $po->ship_to_recipient) }}" {{ $roAttr }}>
                        </label>

                        <label>Address
                            <textarea name="ship_to_address" rows="2" class="po-input{{ $roClass }}" form="poHdrForm" {{ $roAttr }}>{{ old('ship_to_address', $po->ship_to_address) }}</textarea>
                        </label>

                        <label>Phone
                            <input type="text" name="ship_to_phone" class="po-input{{ $roClass }}" value="{{ old('ship_to_phone', $po->ship_to_phone) }}" form="poHdrForm" {{ $roAttr }}>
                        </label>
                    </div>
                    <!-- <div class="po-box-title" style="margin-top: 20px;">Payment / Delivery</div>
                    <div class="po-box-grid">
                        <label>Payment Terms
                            <textarea name="payment_terms" rows="2" class="po-input" form="poHdrForm">{{ old('payment_terms', $po->payment_terms ?? '100% Advance payment to be made in bank before dispatch of delivery.') }}</textarea>
                        </label>

                        <label>Delivery Time
                            <input type="text" name="delivery_time" class="po-input" value="{{ old('delivery_time', $po->delivery_time ?? '14 working days from the date of payment') }}" form="poHdrForm">
                        </label>

                        <label>Delivery Terms
                            <input type="text" name="delivery_terms" class="po-input" value="{{ old('delivery_terms', $po->delivery_terms ?? 'Ex-works Dubai') }}" form="poHdrForm">
                        </label>
                    </div> -->
                </div>
            </div>

            <div class="po-box" style="margin-top: 16px;">
                <div class="po-box-title">Conditions &amp; Terms</div>
                <label>
                    <small>
                        Paste any plain text. We’ll show it exactly as you type (numbers, hyphens, or bullets).
                        Press Enter for a new line; leave a blank line for a paragraph gap.
                    </small>
                    <textarea name="conditions_terms" rows="5" class="po-input{{ $roClass }}" {{ $roAttr }} form="poHdrForm" style="margin-top: 10px;">{{ old('conditions_terms', $po->conditions_terms) }}</textarea>
                </label>
            </div>
        </form>

        <div class="sheet-table-wrap po-wrap" style="margin-top:12px;">
            <table class="sheet-table po-table" id="poRowsTbl">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-sku">ITEM NUMBER / SKU</th>
                        <th class="col-brand">MAKE / Brand</th>
                        <th class="col-desc">Description</th>
                        <th class="col-qty right">Qty</th>
                        <th class="col-aed right">Unit Price (IDR)</th>
                        <th class="col-amt right">Total Price (IDR)</th>
                        <th class="col-actions right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- TEMP DEBUG: remove after checking --}}
                    @foreach($rows as $r)
                    <!-- ROW {{ $r->id }}
                            qty={{ var_export($r->qty, true) }}
                            price={{ var_export($r->price_aed, true) }}
                            amount_col={{ var_export($r->amount, true) }}
                            lineAmount={{ $lineAmount($r) }} -->
                    @endforeach
                    @forelse($rows->values() as $i => $r)
                    <tr data-row-id="{{ $r->id }}">
                        <td class="center">{{ $i + 1 }}</td>

                        <td>
                            @if($canEdit)
                            <input name="sku" class="po-input" form="row-{{ $r->id }}" value="{{ $r->sku }}">
                            @else
                            <div class="readflat">{{ $r->sku }}</div>
                            @endif
                        </td>

                        <td>
                            @if($canEdit)
                            <input name="brand" class="po-input" form="row-{{ $r->id }}" value="{{ $r->brand }}">
                            @else
                            <div class="readflat">{{ $r->brand }}</div>
                            @endif
                        </td>

                        <td class="col-desc">
                            @if($canEdit)
                            <textarea name="description" rows="1" class="po-input" form="row-{{ $r->id }}">{{ $r->description }}</textarea>
                            @else
                            <div class="readflat">{{ $r->description }}</div>
                            @endif
                        </td>

                        <td class="right">
                            @if($canEdit)
                            <input name="qty" class="po-input" form="row-{{ $r->id }}" value="{{ $trim4($r->qty) }}">
                            @else
                            <div class="readflat">{{ $trim4($r->qty) }}</div>
                            @endif
                        </td>

                        <td class="right">
                            @if($canEdit)
                            <input
                                name="price_aed"
                                class="po-input{{ $canEdit ? ' js-aed' : '' }}{{ $roClass }}"
                                inputmode="numeric"
                                form="row-{{ $r->id }}"
                                value="{{ $trim4($r->price_aed) }}">
                            @else
                            <div class="readflat">{{ $trim4($r->price_aed) }}</div>
                            @endif
                        </td>

                        @php
                        $amt = $lineAmount($r);
                        @endphp
                        <td class="right amount-aed" data-amount="{{ $amt }}">{{ $fmtIDR($amt) }}</td>

                        <td class="right">
                            <div class="icon-actions">
                                @if($canEdit)
                                {{-- PATCH (Save) --}}
                                <form id="row-{{ $r->id }}" method="POST"
                                    action="{{ route('po.rows.update', [$po, $r]) }}"
                                    class="row-form">
                                    @csrf @method('PATCH')
                                    <button class="icon-btn icon-save" type="submit" title="Save" aria-label="Save">
                                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"></circle>
                                            <path d="M9 12l2 2 4-4"></path>
                                        </svg>
                                        <span class="sr-only">Save</span>
                                    </button>
                                </form>

                                {{-- DELETE (Row) --}}
                                <form method="POST"
                                    action="{{ route('po.rows.delete', [$po, $r]) }}"
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
                                @endif
                            </div> {{-- .icon-actions --}}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="center text-muted">No rows.</td>
                    </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    {{-- ===== TAX DEBUG (TEMPORARY) ===== --}}
                    <tr>
                        <td colspan="8" style="font-size:11px; color:#b91c1c;">
                            <!-- TAX DEBUG
                            po->id            = {{ $po->id }}
                            po->tax_kind      = {{ var_export($po->tax_kind, true) }}
                            po->ppn_rate      = {{ var_export($po->ppn_rate, true) }}
                            po->tax_value_idr = {{ isset($po->tax_value_idr) ? var_export($po->tax_value_idr, true) : 'NULL' }}

                            kindSaved   = {{ isset($kindSaved) ? var_export($kindSaved, true) : 'NOT SET' }}
                            rawTax      = {{ isset($rawTax) ? var_export($rawTax, true) : 'NOT SET' }}
                            manualSaved = {{ isset($manualSaved) ? var_export($manualSaved, true) : 'NOT SET' }}
                            subtotalIDR = {{ isset($subtotalIDR) ? var_export($subtotalIDR, true) : 'NOT SET' }}
                            taxIDR      = {{ isset($taxIDR) ? var_export($taxIDR, true) : 'NOT SET' }}
                            totalIDR    = {{ isset($totalIDR) ? var_export($totalIDR, true) : 'NOT SET' }}
                            -->
                        </td>
                    </tr>

                    <tr>
                        <th colspan="6" class="right">Subtotal</th>
                        <th class="right" id="ftSubtotal">{{ $fmtIDR($subtotalIDR) }}</th>
                        <th></th>
                    </tr>

                    <tr id="taxRow">
                        <th colspan="6" class="right" id="ftTaxLabel">
                            @if($canEdit)
                            <div class="tax-inline">
                                <input type="hidden" name="tax_kind" id="tax-kind" value="{{ $kindUi }}">
                                <div class="tax-kind-group" role="group" aria-label="Tax kind">
                                    <button type="button" class="tax-kind-btn {{ $kindUi==='ppn' ? 'is-active' : '' }}" data-val="ppn">PPN</button>
                                    <button type="button" class="tax-kind-btn {{ $kindUi==='pph' ? 'is-active' : '' }}" data-val="pph">PPH</button>
                                    <button type="button" class="tax-kind-btn {{ $kindUi==='none' ? 'is-active' : '' }}" data-val="none">No Tax</button>
                                </div>

                                <span id="tax-kind-label-text" style="margin-left:10px;">{{ $taxKindText }}</span>
                            </div>
                            @else
                            <span>{{ $taxKindText }}</span>
                            @endif
                        </th>

                        <th class="right" id="ftTax">
                            @if($canEdit)
                            <input
                                id="taxAmount"
                                name="ppn_rate"
                                type="text"
                                inputmode="numeric"
                                class="po-input money-input {{ $kindUi==='none' ? 'is-hidden' : '' }}"
                                value="{{ $trim4($rateUi) }}"
                                placeholder="0"
                                aria-label="Tax amount (IDR)"
                                {{ $kindUi==='none' ? 'disabled' : '' }}>
                            @else
                            <div class="readflat">{{ $fmtIDR($taxIDR) }}</div>
                            @endif
                        </th>
                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="6" class="right">Total</th>
                        <th class="right" id="ftTotal">{{ $fmtIDR($totalIDR) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="sheet-toolbar">
            @if($canEdit)
            <button
                id="jsAddRow"
                type="button"
                class="sheet-btn sheet-btn-outline"
                data-add-url="{{ route('po.rows.add', $po) }}"
                data-update-url-template="{{ route('po.rows.update', [$po, '__ROW__']) }}"
                data-delete-url-template="{{ route('po.rows.delete', [$po, '__ROW__']) }}"
                data-csrf="{{ csrf_token() }}">+ Add row</button>
            @endif
        </div>
        @php
        $totalInt = (int) $totalIDR;
        try {
        if (!class_exists(\NumberFormatter::class)) throw new \Exception('intl missing');
        $fmt = new \NumberFormatter('id', \NumberFormatter::SPELLOUT);
        $words = $fmt->format($totalInt);
        if ($words === false) throw new \Exception('spellout failed');
        $amountWords = ucfirst($words).' rupiah';
        } catch (\Throwable $e) {
        $amountWords = $fmtIDR($totalInt);
        }
        @endphp

        <div class="sheet-summary" style="grid-template-columns: 1fr;">
            <div class="sum-item">
                <div class="sum-label" style="margin-bottom:6px;">Amount in Words</div>
                <div id="amountWords" class="readflat">
                    {{ $amountWords }}
                </div>
            </div>
        </div>

        @php
        $terms = (string) ($po->conditions_terms ?? '');
        @endphp

        @if(trim($terms) !== '')
        <div class="po-box" style="margin-top:16px;">
            <div class="po-box-title">Conditions &amp; Terms</div>
            <div class="terms-plain">{{ $terms }}</div>
        </div>
        @endif

        @php
        $canDeletePo = !$isClosedYear && auth()->user()?->can('delete', $po);
        @endphp

        <div class="sheet-toolbar" style="justify-content: flex-end;">
            @if($canDeletePo)
            <form id="deletePoForm" action="{{ route('po.destroy', $po) }}" method="POST">
                @csrf @method('DELETE')
                <button type="button" id="btnDeletePo"
                    class="sheet-btn sheet-btn-ghost"
                    style="border-color:#b91c1c;color:#b91c1c">
                    Delete PO
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Upload / manage modal --}}
    <div id="poatt-upload" class="poatt-modal poatt-hidden" aria-hidden="true" data-can-update="{{ $canEdit ? '1' : '0' }}">
        <div class=" poatt-panel" role="dialog" aria-modal="true" aria-labelledby="poatt-upload-title">
            <div class="poatt-head">
                <h3 id="poatt-upload-title">Attachments</h3>
                <button type="button" class="poatt-close" aria-label="Close">×</button>
            </div>

            <div class="poatt-body">

                {{-- READ-ONLY NOTICE for consultants --}}
                @unless($canUpdate)
                <div class="poatt-muted" style="margin-bottom:10px;">
                    Read-only: you can view and download from the “Attachments Viewer”.
                </div>
                @endunless

                {{-- DARK HERO (upload bar + drag area) — only for users who can update --}}
                @can('update', $po)
                <div class="poatt-hero">
                    <form id="poatt-form" class="poatt-uploadbar poatt-uploadbar--pretty">
                        <input type="file" name="files[]" id="poatt-files" class="poatt-file" multiple>
                        <button type="button" id="poatt-browse" class="sheet-btn sheet-btn-primary" aria-controls="poatt-files">
                            Browse files
                        </button>
                        <button type="submit" class="sheet-btn">Upload</button>
                        <span class="poatt-muted" id="poatt-msg">Select files to upload…</span>
                    </form>

                    <!-- optional drag area -->
                    <div class="poatt-dropzone" id="poatt-drop">
                        <div class="poatt-drop-inner">
                            <div class="poatt-drop-title">Drag & drop files here</div>
                            <div class="poatt-drop-sub">PDF, Images, up to 25MB each</div>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- LIST (2-col grid, no view/download here, only delete) -->
                <div id="poatt-list" class="poatt-list poatt-list--cards">
                    <!-- filled by JS with .poatt-item rows like:
                    <div class="poatt-item">
                    <div class="poatt-meta">
                        <div class="poatt-name" title="filename.pdf">filename.pdf</div>
                        <div class="poatt-sub">1.2 MB · uploaded 24 Oct 2025</div>
                    </div>
                    <div class="poatt-actions">
                        <button class="poatt-delete" data-id="ATT_ID" aria-label="Delete attachment">Delete</button>
                    </div>
                    </div>
                    -->
                </div>
            </div>
        </div>
    </div>

    {{-- PO Attachments Viewer (two-pane) --}}
    <div id="poatt-stacked" class="poatt-modal poatt-hidden" aria-hidden="true">
        <div class="poatt-panel poatt-panel--viewer" role="dialog" aria-modal="true" aria-labelledby="poatt-viewer-title">
            <div class="poatt-head">
                <h3 id="poatt-viewer-title">Attachments Viewer</h3>
                <div class="poatt-head-actions">
                    <a id="poatt-dl-all" class="sheet-btn sheet-btn-primary" href="#" download style="display:none">Download All</a>
                    <button type="button" class="poatt-close" aria-label="Close">×</button>
                </div>
            </div>

            <!-- NEW: two-pane viewer -->
            <div class="poatt-view">
                <aside id="poatt-side" class="poatt-side" aria-label="Files list"><!-- filled by JS --></aside>

                <section class="poatt-preview" aria-live="polite">
                    <div class="poatt-toolbar">
                        <button type="button" class="poatt-zoom" data-zoom="-">−</button>
                        <span class="poatt-zoomval" id="poatt-zoomval">100%</span>
                        <button type="button" class="poatt-zoom" data-zoom="+">+</button>
                        <span class="poatt-tool-sep"></span>
                        <button type="button" class="poatt-fit" data-fit="w">Fit width</button>
                        <button type="button" class="poatt-fit" data-fit="1">100%</button>
                        <a id="poatt-dl-one" class="sheet-btn sheet-btn-ghost poatt-dl-one" href="#" download>Download</a>
                    </div>
                    <div id="poatt-canvas" class="poatt-canvas"><!-- media injected by JS --></div>
                </section>
            </div>
        </div>
    </div>

</div>