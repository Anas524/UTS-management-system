@extends('layouts.app')
@section('title','Create Purchase Order')

@php
$fmtIDR = fn(int|float $n) => 'IDR '.number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<div class="sheet-wrap">
  <div class="sheet-card">
    <div class="sheet-head">
      <div>
        <div class="sheet-company">PT: Universal Trade Services</div>
        <h1 class="sheet-title">Purchase Order</h1>
      </div>

      <div class="sheet-head-actions">
        <a href="{{ route('po.index') }}" class="sheet-btn sheet-btn-outline">← Back to list</a>

        <!-- <button type="button"
          id="btnImport"
          class="sheet-btn sheet-btn-primary"
          data-import-url="{{ route('po.import') }}"
          data-csrf="{{ csrf_token() }}">
          Import
        </button>
        <input id="importFile" type="file" accept=".pdf,.png,.jpg,.jpeg" hidden> -->
      </div>
    </div>

    <form method="POST" action="{{ route('po.store') }}" id="poCreateForm" data-find-url="{{ route('po.find') }}" data-get-url="{{ route('po.get') }}">
      <input type="text" autocomplete="username" style="position:absolute;left:-9999px;opacity:0;height:0;width:0">
      <input type="password" autocomplete="new-password" style="position:absolute;left:-9999px;opacity:0;height:0;width:0">

      @csrf

      {{-- Header fields (same styling as show.blade) --}}
      <div class="admin-grid">
        <div class="field-row">
          <label>PO Number</label>
          <input name="po_number" class="po-input" value="{{ old('po_number') }}">
        </div>

        <div class="field-row">
          <label>Date</label>
          <input type="date" name="po_date" class="po-input" value="{{ old('po_date') }}">
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
          <input name="address" class="po-input" value="{{ old('address') }}">
        </div>
      </div>

      @php
      $statusVal = old('status', 'open');
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
        <div class="po-box">
          <div class="po-box-title">Supplier Information</div>
          <div class="po-box-grid">
            <label>Company Name</label>
            <div class="auto-wrap">
              <!-- Visible field (fake name so Chrome won’t autofill saved creds) -->
              <input
                id="supCompanyVis"
                type="search"
                class="po-input"
                name="__sup_company_vis"
                value="{{ old('sup_company') }}"
                autocomplete="off"
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false" />
              <!-- dropdown list -->
              <div id="supMenu" class="po-autolist" hidden></div>

              <!-- Real submitted value -->
              <input id="supCompany" type="hidden" name="sup_company" value="{{ old('sup_company') }}">
            </div>
            <label>Company Address
              <textarea name="sup_address" rows="2" class="po-input">{{ old('sup_address') }}</textarea>
            </label>
            <label>Phone Number
              <input type="text" name="sup_phone" class="po-input" value="{{ old('sup_phone') }}">
            </label>
            <label>E-mail
              <input type="email" name="sup_email" class="po-input" value="{{ old('sup_email') }}">
            </label>
            <label>NPWP
              <input type="text" name="sup_npwp" class="po-input" value="{{ old('sup_npwp') }}">
            </label>
          </div>
        </div>

        <div class="po-box">
          <div class="po-box-title">Ship To (PT. UNIVERSAL TRADE SERVICES)</div>
          <div class="po-box-grid">
            <label>Recipient
              <input type="text" name="ship_to_recipient" class="po-input" value="{{ old('ship_to_recipient') }}">
            </label>

            <label>Address
              <textarea name="ship_to_address" rows="2" class="po-input">{{ old('ship_to_address') }}</textarea>
            </label>

            <label>Phone
              <input type="text" name="ship_to_phone" class="po-input" value="{{ old('ship_to_phone') }}">
            </label>
          </div>
          <div class="po-box-title" style="margin-top: 20px;">Payment / Delivery</div>
          <div class="po-box-grid">
            <label>Payment Terms
              <textarea name="payment_terms" rows="2" class="po-input">{{ old('payment_terms', '100% Advance payment to be made in bank before dispatch of delivery.') }}</textarea>
            </label>

            <label>Delivery Time
              <input type="text" name="delivery_time" class="po-input" value="{{ old('delivery_time', '14 working days from the date of payment') }}">
            </label>

            <label>Delivery Terms
              <input type="text" name="delivery_terms" class="po-input" value="{{ old('delivery_terms', 'Ex-works Dubai') }}">
            </label>
          </div>
        </div>
      </div>

      {{-- Rows table (same columns as show.blade) --}}
      <div class="sheet-table-wrap po-wrap" style="margin-top:12px;">
        <table class="sheet-table legacy-table po-table" id="poRowsTbl">
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th class="col-sku">ITEM NUMBER / SKU</th>
              <th class="col-brand">MAKE / Brand</th>
              <th class="col-desc">Description</th>
              <th class="col-qty right">Qty</th>
              <th class="col-unitprice right">Unit Price (IDR)</th>
              <th class="col-total right">Total Price (IDR)</th>
              <th class="col-actions right">Actions</th>
            </tr>
          </thead>

          <tbody id="createTbody">
            {{-- rows injected by JS --}}
          </tbody>

          <tfoot>
            <tr>
              <th colspan="6" class="right">Subtotal</th>
              <th class="right" id="ftSubtotal">IDR 0</th>
              <th></th>
            </tr>
            <tr id="taxRow">
              <th colspan="6" class="right" id="ftTaxLabel">Pajak Pertambahan Nilai (PPN) 0%</th>
              <th class="right" id="ftTax">IDR 0</th>
              <th></th>
            </tr>
            <tr>
              <th colspan="6" class="right">Total</th>
              <th class="right" id="ftTotal">IDR 0</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
        <div class="sheet-summary" style="grid-template-columns: 1fr;">
          <div class="sum-item">
            <div class="sum-label" style="margin-bottom:6px;">Amount in Words</div>
            <div id="amountWords" class="readflat">Nol rupiah</div>
          </div>
        </div>
      </div>

      <div class="sheet-toolbar">
        <button type="button" class="sheet-btn sheet-btn-outline" id="jsAddRow">+ Add Row</button>
        <button type="submit" class="sheet-btn sheet-btn-primary" style="margin-left:auto;">Save PO</button>
      </div>
    </form>
  </div>
</div>