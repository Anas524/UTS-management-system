@extends('layouts.app')
@section('title', 'Purchase Order #'.$po->po_number)

@php
// Always IDR integers with Indonesian grouping
$fmtIDR = fn(int $n) => 'IDR '.number_format($n, 0, ',', '.');

// Safe int parser (handles "3.000" or 3000)
$toInt = fn($v) => (int) preg_replace('/[^\d]/', '', (string) $v);

$rows = $po->rows ?? collect();

// Subtotal in IDR = sum(unit * qty)
$subtotalIDR = $rows->sum(function ($r) use ($toInt) {
$unit = $toInt($r->price_aed ?? 0); // stored as IDR integer; robust if string with dots
$qty = (float) ($r->qty ?? 0);
return (int) round($unit * $qty);
});

$kind = strtolower($po->tax_kind ?? 'ppn');
$rate = (float) ($po->ppn_rate ?? 0);
$taxIDR = $kind === 'none' ? 0 : (int) round($subtotalIDR * $rate / 100);
$totalIDR = $subtotalIDR + $taxIDR;

$rateTxt = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

// Labels
$names = [
'ppn' => ['short' => 'PPN', 'full' => 'Pajak Pertambahan Nilai'],
'pph' => ['short' => 'PPH', 'full' => 'Pajak Penghasilan'],
'none' => null,
];
if (!isset($names[$kind])) $kind = 'ppn';
$entry = $names[$kind];
$taxLabelFull = $entry ? ($entry['full'].' ('.$entry['short'].') '.$rateTxt.'%') : '';
@endphp

@section('content')

<div class="sheet-wrap">
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
                        data-csrf="{{ csrf_token() }}">
                        Attachments
                        <span class="att-badge" id="poatt-count">{{ $po->attachments_count ?? 0 }}</span>
                        <svg class="att-caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                            <path d="M5 7l5 6 5-6" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </button>

                    <div class="att-menu" role="menu" aria-label="Attachments menu">
                        <button class="att-item js-att-manage" role="menuitem">Manage uploads</button>
                        <button class="att-item js-att-view" role="menuitem">View attachments</button>
                    </div>
                </div>

                <button type="submit" form="poHdrForm" class="sheet-btn sheet-btn-primary">Save PO</button>
            </div>
        </div>

        {{-- Header form (same view for read and edit) --}}
        <form method="POST" action="{{ route('po.update',$po) }}" id="poHdrForm" data-update-url="{{ route('po.update',$po) }}" data-csrf="{{ csrf_token() }}">
            @csrf @method('PATCH')
            <div class="admin-grid">
                <div class="field-row">
                    <label>PO Number</label>
                    <input name="po_number" class="po-input" value="{{ old('po_number',$po->po_number) }}">
                </div>
                <div class="field-row">
                    <label>Date</label>
                    <input type="date" name="po_date" class="po-input" value="{{ old('po_date', $po->po_date_for_input) }}">
                </div>
                <div class="field-row field-row--taxdd">
                    <label>Tax</label>

                    <div class="taxdd-inline">
                        <!-- custom dropdown (hidden value + trigger + menu) -->
                        @php
                        $taxKindVal = strtolower(old('tax_kind', $po->tax_kind ?? 'ppn'));
                        $taxKindLabel = $taxKindVal === 'ppn' ? 'PPN — Pajak Pertambahan Nilai'
                        : ($taxKindVal === 'pph' ? 'PPH — Pajak Penghasilan'
                        : ($taxKindVal === 'none' ? 'No Tax' : 'PPN / PPH'));
                        $rateDefault = old('ppn_rate', $po->ppn_rate ?? 0);
                        @endphp

                        <div class="tax-actions">
                            <input type="hidden" name="tax_kind" id="tax-kind" value="{{ $taxKindVal }}">
                            <button type="button" class="tax-trigger" aria-haspopup="menu" aria-expanded="false">
                                <span id="tax-kind-label">{{ $taxKindLabel }}</span>
                                <svg class="tax-caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                                    <path d="M5 7l5 6 5-6" fill="none" stroke="currentColor" stroke-width="2" />
                                </svg>
                            </button>

                            <div class="tax-menu" role="menu" aria-label="Tax menu">
                                <button class="tax-item" role="menuitem" data-val="ppn">PPN — Pajak Pertambahan Nilai</button>
                                <button class="tax-item" role="menuitem" data-val="pph">PPH — Pajak Penghasilan</button>
                                <button class="tax-item" role="menuitem" data-val="none">No Tax</button>
                            </div>
                        </div>

                        <!-- rate + percent -->
                        <div class="input-group">
                            <input
                                id="ppnRate"
                                name="ppn_rate"
                                type="number"
                                step="0.01"
                                class="po-input"
                                value="{{ $rateDefault }}"
                                placeholder="0"
                                aria-label="Tax rate">
                            <input type="text" class="po-input" value="%" readonly tabindex="-1" aria-hidden="true"
                                style="width:52px; text-align:center">
                        </div>
                    </div>
                </div>
                <div class="field-row" style="grid-column:1/-1;">
                    <label>Address</label>
                    <input name="address" class="po-input" value="{{ old('address',$po->address) }}">
                </div>
            </div>

            @php
            $statusVal = old('status', $po->status ?? 'open');
            $statusLabel = ucfirst(str_replace('_', ' ', $statusVal));
            @endphp
            <div class="field-row field-row--status">
                <label>Status</label>

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
            </div>

            {{-- Supplier & Terms --}}
            <div class="po-info-grid">
                {{-- Supplier (left) --}}
                <div class="po-box">
                    <div class="po-box-title">Supplier Information</div>
                    <div class="po-box-grid">
                        <label>Company Name
                            <input type="text" name="sup_company" class="po-input" value="{{ old('sup_company',$po->sup_company) }}" form="poHdrForm">
                        </label>
                        <label>Company Address
                            <textarea name="sup_address" rows="2" class="po-input" form="poHdrForm">{{ old('sup_address',$po->sup_address) }}</textarea>
                        </label>
                        <label>Phone Number
                            <input type="text" name="sup_phone" class="po-input" value="{{ old('sup_phone',$po->sup_phone) }}" form="poHdrForm">
                        </label>
                        <label>E-mail
                            <input type="email" name="sup_email" class="po-input" value="{{ old('sup_email',$po->sup_email) }}" form="poHdrForm">
                        </label>
                        <label>NPWP
                            <input type="text" name="sup_npwp" class="po-input" value="{{ old('sup_npwp',$po->sup_npwp) }}" form="poHdrForm">
                        </label>
                    </div>
                </div>

                <div class="po-box">
                    <div class="po-box-title">Ship To (PT. UNIVERSAL TRADE SERVICES)</div>
                    <div class="po-box-grid">
                        <label>Recipient
                            <input type="text" name="ship_to_recipient" class="po-input" form="poHdrForm" value="{{ old('ship_to_recipient', $po->ship_to_recipient) }}">
                        </label>

                        <label>Address
                            <textarea name="ship_to_address" rows="2" class="po-input" form="poHdrForm">{{ old('ship_to_address', $po->ship_to_address) }}</textarea>
                        </label>

                        <label>Phone
                            <input type="text" name="ship_to_phone" class="po-input" value="{{ old('ship_to_phone', $po->ship_to_phone) }}" form="poHdrForm">
                        </label>
                    </div>
                    <div class="po-box-title" style="margin-top: 20px;">Payment / Delivery</div>
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
                    </div>
                </div>
            </div>

            <div class="po-box" style="margin-top: 16px;">
                <div class="po-box-title">Conditions &amp; Terms</div>
                <label>
                    <small>
                        Paste any plain text. We’ll show it exactly as you type (numbers, hyphens, or bullets).
                        Press Enter for a new line; leave a blank line for a paragraph gap.
                    </small>
                    <textarea name="conditions_terms" rows="5" class="po-input" form="poHdrForm" style="margin-top: 10px;">{{ old('conditions_terms', $po->conditions_terms) }}</textarea>
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
                    @forelse($rows->values() as $i => $r)
                    <tr data-row-id="{{ $r->id }}">
                        <td class="center">{{ $i + 1 }}</td>

                        <td>
                            <input name="sku" class="po-input" form="row-{{ $r->id }}" value="{{ $r->sku }}">
                        </td>

                        <td>
                            <input name="brand" class="po-input" form="row-{{ $r->id }}" value="{{ $r->brand }}">
                        </td>

                        <td class="col-desc">
                            <textarea name="description" rows="1" class="po-input" form="row-{{ $r->id }}">{{ $r->description }}</textarea>
                        </td>

                        <td class="right">
                            <input name="qty" class="po-input" form="row-{{ $r->id }}" value="{{ $r->qty ?: 0 }}">
                        </td>

                        <td class="right">
                            <input
                                name="price_aed"
                                class="po-input js-aed"
                                inputmode="numeric"
                                form="row-{{ $r->id }}"
                                value="{{ $r->price_aed !== null ? number_format($r->price_aed, 0, ',', '.') : '' }}">
                        </td>

                        @php
                        $amt = (int) $toInt($r->price_aed ?? 0) * (float)($r->qty ?: 0);
                        @endphp
                        <td class="right amount-aed">{{ $fmtIDR((int) round($amt)) }}</td>

                        <td class="right">
                            <div class="icon-actions">
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
                                @can('delete', $po)
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
                                @endcan
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
                    <tr>
                        <th colspan="6" class="right">Subtotal</th>
                        <th class="right" id="ftSubtotal">{{ $fmtIDR($subtotalIDR) }}</th>
                        <th></th>
                    </tr>

                    <tr id="taxRow" class="{{ $kind==='none' ? 'is-hidden' : '' }}">
                        <th colspan="6" class="right" id="ftTaxLabel">{{ $taxLabelFull }}</th>
                        <th class="right" id="ftTax">{{ $fmtIDR($taxIDR) }}</th>
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
            <button
                id="jsAddRow"
                type="button"
                class="sheet-btn sheet-btn-outline"
                data-add-url="{{ route('po.rows.add', $po) }}"
                data-update-url-template="{{ route('po.rows.update', [$po, '__ROW__']) }}"
                data-delete-url-template="{{ route('po.rows.delete', [$po, '__ROW__']) }}"
                data-csrf="{{ csrf_token() }}">+ Add row</button>
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

        <div class="sheet-toolbar" style="justify-content: flex-end;">
            <form id="deletePoForm" action="{{ route('po.destroy', $po) }}" method="POST">
                @csrf @method('DELETE')
                <button type="button" id="btnDeletePo"
                    class="sheet-btn sheet-btn-ghost"
                    style="border-color:#b91c1c;color:#b91c1c">
                    Delete PO
                </button>
            </form>
        </div>
    </div>

    {{-- Upload / manage modal --}}
    <div id="poatt-upload" class="poatt-modal poatt-hidden" aria-hidden="true">
        <div class="poatt-panel" role="dialog" aria-modal="true" aria-labelledby="poatt-upload-title">
            <div class="poatt-head">
                <h3 id="poatt-upload-title">Attachments</h3>
                <button type="button" class="poatt-close" aria-label="Close">×</button>
            </div>

            <div class="poatt-body">
                <!-- DARK HERO (same vibe as .poatt-view) -->
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