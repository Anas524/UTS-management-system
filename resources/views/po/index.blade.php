@extends('layouts.app')
@section('title','Purchase Orders')

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
        <a href="{{ route('po.create') }}" class="sheet-btn sheet-btn-outline">+ Create New PO</a>
      </div>
    </div>

    @if(session('status'))
    <div class="sheet-alert success">{{ session('status') }}</div>
    @endif

    {{-- Totals bar (uses $subtotalFils, $taxFils, $totalFils from controller) --}}
    @php $fmtMoney = fn(int $f)=> 'IDR '.number_format($f/100, 2, '.', ','); @endphp
    <div class="stats-wrap stats-inline">
      <div class="stat-card">
        <div class="stat-label">Subtotal</div>
        <div class="stat-value">{{ $fmtMoney($subtotalFils) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">PPN / PPH (sum)</div>
        <div class="stat-value">{{ $fmtMoney($taxFils) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-value">{{ $fmtMoney($totalFils) }}</div>
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
            <th>Supplier Name</th>
            <th class="right">Subtotal</th>
            <th class="right">PPN / PPH %</th>
            <th class="right">Total</th>
            <th>Status</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $po)
          @php
          $fmt = fn($n) => 'IDR '.number_format((float)$n, 2, '.', ',');

          $vatRate = (float)($po->ppn_rate ?? $po->vat_rate ?? 0);
          $vatTxt = rtrim(rtrim(number_format($vatRate, 2, '.', ''), '0'), '.');

          // Subtotal in IDR (prefer withSum result)
          $subtotalFils = isset($po->subtotal_fils) ? (int)$po->subtotal_fils : (int)($po->rows?->sum('amount') ?? 0);
          $subtotalMajor = round($subtotalFils / 100, 2);

          $vatAmount = round($subtotalMajor * $vatRate / 100, 2);
          $totalMajor = round($subtotalMajor + $vatAmount, 2);

          $status = $po->status ?? 'open';
          $badgeClass = match($status){
          'closed' => 'badge badge-green',
          'awaiting_response' => 'badge badge-amber',
          'transferred' => 'badge badge-indigo',
          default => 'badge badge-slate',
          };
          $statusText = $po->status_label; // uses accessor
          $dateStr = $po->po_date ? \Illuminate\Support\Carbon::parse($po->po_date)->format('d-m-y') : '—';
          @endphp
          <tr>
            <td class="center">{{ $rowNoStart + $loop->index }}</td>
            <td>{{ $po->po_number ?? $po->id }}</td>
            <td>{{ $dateStr }}</td>
            <td>{{ \Illuminate\Support\Str::limit($po->prepared_by ?? '—', 48) }}</td>
            <td class="right">{{ $fmt($subtotalMajor) }}</td>
            <td class="right">{{ $vatTxt }}% ({{ $fmt($vatAmount) }})</td>
            <td class="right">{{ $fmt($totalMajor) }}</td>
            <td>
              <span class="{{ $badgeClass }}">{{ $statusText }}</span>
            </td>
            <td class="right">
              <a class="table-btn primary" href="{{ route('po.show', $po) }}">Open</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="empty">No purchase orders yet.</td>
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