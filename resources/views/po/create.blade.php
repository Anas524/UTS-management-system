@extends('layouts.app')
@section('title','Create Purchase Order')

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
      @csrf

      {{-- Header fields (same styling as show.blade) --}}
      <div class="admin-grid">
        <div class="field-row">
          <label>Supplier Name</label>
          <input id="fldSupplier" name="prepared_by" class="po-input" value="{{ old('prepared_by') }}" autocomplete="off">
          <div id="supMenu" class="po-autolist" hidden></div>
        </div>

        <div class="field-row">
          <label>PO Number</label>
          <input id="fldPoNumber" name="po_number" class="po-input" value="{{ old('po_number') }}" autocomplete="off">
          <div id="poMenu" class="po-autolist" hidden></div>
        </div>

        <div class="field-row">
          <label>Date</label>
          <input type="date" name="po_date" class="po-input" value="{{ old('po_date') }}">
        </div>

        <div class="field-row field-row--tax">
          <label>Tax</label>

          <div class="tax-inline">
            {{-- VAT label styled as input --}}
            <input
              type="text"
              class="po-input"
              value="PPN / PPH"
              readonly
              tabindex="-1"
              aria-label="Tax type">

            {{-- Rate input + % suffix --}}
            <div class="input-group">
              <input
                name="ppn_rate"
                type="number"
                step="0.01"
                class="po-input"
                value="{{ old('ppn_rate', 0) }}"
                placeholder="0"
                aria-label="VAT rate">

              <input
                type="text"
                class="po-input"
                value="%"
                readonly
                tabindex="-1"
                aria-hidden="true"
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
      $statusVal = old('status', $po->status ?? 'open');
      $statusLabel = ucfirst(str_replace('_', ' ', $statusVal));
      @endphp
      <div class="field-row field-row--status">
        <label>Status</label>

        <div class="status-actions">
          <input type="hidden" name="status" id="po-status" value="{{ $po->status ?? 'open' }}">

          <button type="button" class="status-trigger" aria-haspopup="menu" aria-expanded="false">
            <span id="po-status-label">{{ ucfirst(str_replace('_',' ', $po->status ?? 'open')) }}</span>
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
            <label>Company Name
              <input type="text" name="sup_company" class="po-input" value="{{ old('sup_company') }}">
            </label>
            <label>Company Address
              <textarea name="sup_address" rows="2" class="po-input">{{ old('sup_address') }}</textarea>
            </label>
            <label>Phone Number
              <input type="text" name="sup_phone" class="po-input" value="{{ old('sup_phone') }}">
            </label>
            <label>E-mail
              <input type="email" name="sup_email" class="po-input" value="{{ old('sup_email') }}">
            </label>
            <label>Contact Person
              <input type="text" name="sup_contact_person" class="po-input" value="{{ old('sup_contact_person') }}">
            </label>
            <label>Contact Phone
              <input type="text" name="sup_contact_phone" class="po-input" value="{{ old('sup_contact_phone') }}">
            </label>
            <label>Contact Email
              <input type="email" name="sup_contact_email" class="po-input" value="{{ old('sup_contact_email') }}">
            </label>
          </div>
        </div>

        <div class="po-box">
          <div class="po-box-title">Payment / Delivery</div>

          <div class="po-box-read">
            <div class="po-read-row">
              <div class="po-read-label">Payment Terms</div>
              <div class="po-read-value">100% Advance payment to be made in bank before dispatch of delivery.</div>
            </div>
            <div class="po-read-row">
              <div class="po-read-label">Delivery Time</div>
              <div class="po-read-value">14 working days from the date of payment</div>
            </div>
            <div class="po-read-row">
              <div class="po-read-label">Delivery Terms</div>
              <div class="po-read-value">Ex-works Dubai</div>
            </div>
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
              <th class="right" id="ftSubtotal">IDR 0.00</th>
              <th></th>
            </tr>
            <tr>
              <th colspan="6" class="right" id="ftTaxLabel">TAX 0%</th>
              <th class="right" id="ftTax">IDR 0.00</th>
              <th></th>
            </tr>
            <tr>
              <th colspan="6" class="right">Total</th>
              <th class="right" id="ftTotal">IDR 0.00</th>
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