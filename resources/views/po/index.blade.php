@extends('layouts.app')
@section('title','Purchase Orders')

@php $isConsultant = auth()->user()?->role === 'consultant';  use App\Support\Num; @endphp

@section('content')
<div class="sheet-wrap">
  <div class="sheet-card">
    <div class="sheet-head">
      <div>
        <div class="sheet-company">PT: Universal Trade Services</div>
        <h1 class="sheet-title">Purchase Orders</h1>
        <div class="sheet-sub">List of your POs</div>
      </div>
      <div class="sheet-head-actions">
        <form id="poMonthFilter" method="GET" action="{{ route('po.index') }}" class="dd-month">
          <input type="hidden" name="m" id="monthVal" value="{{ (int)$m }}">
          <div class="ddm" data-current="{{ (int)$m }}">
            <button type="button"
              class="ddm__trigger"
              aria-haspopup="listbox"
              aria-expanded="false"
              aria-label="Filter by month">
              <span class="ddm__text">{{ $months[$m] ?? 'All months' }}</span>
              <svg class="ddm__caret" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                <path d="M6 8l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" />
              </svg>
            </button>

            <div class="ddm__menu" role="listbox">
              @foreach($months as $val => $label)
              <button type="button"
                role="option"
                class="ddm__item {{ (int)$m === (int)$val ? 'is-active' : '' }}"
                data-value="{{ $val }}">
                {{ $label }}
              </button>
              @endforeach
            </div>
          </div>
        </form>

        <a href="{{ route('dashboard') }}" class="sheet-btn sheet-btn-ghost">← Back</a>

        @can('create', App\Models\PurchaseOrder::class)
          <a href="{{ route('po.create') }}" class="sheet-btn sheet-btn-outline">+ Create New PO</a>
        @endcan
      </div>
      
      @if($isConsultant)
        <span class="sheet-head-note">Read-only mode: you can open POs and download files.</span>
      @endif
    </div>

    @if(session('status'))
    <div class="sheet-alert success">{{ session('status') }}</div>
    @endif

    {{-- Totals bar (uses $subtotalFils, $taxFils, $totalFils from controller) --}}
    @php
    $fmt = fn($n) => \App\Support\Num::fmtMoney((string)$n, prefix: 'IDR ');
    @endphp
    <div class="stats-wrap stats-inline">
      <div class="stat-card">
        <div class="stat-label">Subtotal</div>
        <div class="stat-value">{{ $fmt($subtotalFils) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Tax</div>
        <div class="stat-value">{{ $fmt($taxFils) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-value">{{ $fmt($totalFils) }}</div>
      </div>
    </div>

    @php
    // Row numbering start, works for paginated and non-paginated $list
    $rowNoStart = ($list instanceof \Illuminate\Contracts\Pagination\Paginator) ? ($list->firstItem() ?? 1) : 1;
    @endphp

    <div class="sheet-table-wrap">
      <table class="sheet-table">
        <thead>
          <tr>
            <th style="width:52px;" class="center">No</th>
            <th style="width:140px;">PO #</th>
            <th>Date</th>
            <th>Company Name</th>
            <th class="right">Subtotal</th>
            <th class="right">Tax</th>
            <th class="right">Total</th>
            <th>Status</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @php
            // Helpers
            $fmt0  = fn($n) => \App\Support\Num::fmtMoney((string)$n, prefix: 'IDR ');
          @endphp

          @forelse($list as $po)
          @php
            // 1) Subtotal — ALWAYS recompute from price * qty (rounded integer rupiah)
            $rowSubtotalInt = (int) (($po->rows ?? collect())->sum(function ($r) {
                $unit = (float)($r->price_aed ?? 0);
                $qty  = (float)($r->qty ?? 0);
                return (int) round($unit * $qty, 0);
            }));

            // 2) Tax — absolute IDR stored in ppn_rate
            $kind   = strtolower($po->tax_kind ?? 'ppn');
            $taxInt = ($kind === 'none') ? 0 : (int) round((float)($po->ppn_rate ?? 0));

            // 3) Total — PPH subtracts, others add
            $rowTotalInt = ($kind === 'pph')
                ? max(0, $rowSubtotalInt - $taxInt)
                : $rowSubtotalInt + $taxInt;

            // Meta
            $status     = $po->status ?? 'open';
            $badgeClass = match($status){
                'closed' => 'badge badge-green',
                'awaiting_response' => 'badge badge-amber',
                'transferred' => 'badge badge-indigo',
                default => 'badge badge-slate',
            };
            $statusText = $po->status_label;
            $dateStr    = $po->po_date ? \Illuminate\Support\Carbon::parse($po->po_date)->format('d-m-y') : '—';
          @endphp
          <tr>
            <td class="center">{{ $rowNoStart + $loop->index }}</td>
            <td>{{ $po->po_number ?? $po->id }}</td>
            <td>{{ $dateStr }}</td>
            <td>{{ \Illuminate\Support\Str::limit($po->sup_company ?? '—', 48) }}</td>
            <td class="right">{{ $fmt0($rowSubtotalInt) }}</td>
            <td class="right">
              @if($kind === 'none')
                No Tax
              @else
                {{ $fmt0($taxInt) }}
              @endif
            </td>
            <td class="right">{{ $fmt0($rowTotalInt) }}</td>
            <td>
              <span class="{{ $badgeClass }}">{{ $statusText }}</span>
            </td>
            <td class="right">
              <a class="table-btn primary" href="{{ route('po.show', $po) }}">Open</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="empty">No purchase orders yet.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($list instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="sheet-paginate">
      {{ $list->links() }}
    </div>
    @endif
  </div>
</div>
@endsection