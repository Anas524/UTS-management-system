@extends('layouts.app')
@section('title','Expense Sheets')

@php
$isConsultant = auth()->user()?->role === 'consultant';
@endphp

@section('content')
<div class="sheet-wrap">
    <div class="sheet-card">
        <div class="sheet-head">
            <div>
                <div class="sheet-company">PT: Universal Trade Services</div>
                <h1 class="sheet-title">Expense Sheets</h1>
            </div>

            <div class="sheet-head-actions">
                <a href="{{ route('dashboard') }}" class="sheet-btn sheet-btn-ghost">← Back</a>
                @can('create', App\Models\ExpenseSheet::class)
                <button class="sheet-btn sheet-btn-primary" data-modal-open="#modalCreate">+ Add Sheet</button>
                @endcan
            </div>
        </div>

        {{-- Year toolbar --}}
        <div class="year-toolbar" style="display:flex;gap:8px;align-items:center;justify-content:flex-end;margin:12px 0;">

            {{-- Year selector: only if there are multiple years --}}
            @if($multiYear)
            <form method="GET" action="{{ route('expenses.index') }}">
                <div class="year-dd" id="year-dd">
                    <input type="hidden" name="year" id="yearInput" value="{{ $activeYear }}">
                    <button type="button" class="year-dd__button" id="yearBtn" aria-haspopup="listbox" aria-expanded="false">
                        <span id="yearLabel">{{ $activeYear }}</span>
                        <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18">
                            <polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <ul class="year-dd__list" id="yearList" role="listbox" tabindex="-1">
                        @foreach($yearOptions as $y)
                        <li role="option"
                            class="year-dd__option {{ (int)$y === (int)$activeYear ? 'is-selected' : '' }}"
                            aria-selected="{{ (int)$y === (int)$activeYear ? 'true' : 'false' }}"
                            data-value="{{ $y }}">{{ $y }}</li>
                        @endforeach
                    </ul>
                </div>
            </form>
            @else
            {{-- Single year: show a small pill instead of a dropdown --}}
            <div class="year-pill" style="padding:6px 10px;border:1px solid #dfe7ef;border-radius:10px;background:#fff;font-weight:600;">
                {{ $activeYear }}
            </div>
            @endif

            {{-- Year actions: hidden for consultants --}}
            @if(!$isConsultant)
            {{-- Show Close if ANY of *my* sheets in this year are open --}}
            @if($hasOpen)
            <form method="POST" action="{{ route('expenses.year.close', $activeYear) }}"
                onsubmit="return confirm('Close {{ $activeYear }}? You will not be able to edit unless you reopen.');">
                @csrf
                <button type="submit" class="sheet-btn sheet-btn-outline">Close {{ $activeYear }}</button>
            </form>
            @endif

            {{-- Always allow opening next year --}}
            <form method="POST" action="{{ route('expenses.year.openNext', $activeYear) }}"
                onsubmit="return confirm('Open {{ $activeYear + 1 }} and create sheets if missing?');">
                @csrf
                <button type="submit" class="sheet-btn sheet-btn-primary">Open {{ $activeYear + 1 }}</button>
            </form>

            {{-- Show Reopen only when NONE of my sheets are open but at least one is closed --}}
            @if($hasClosed && !$hasOpen)
            <form method="POST" action="{{ route('expenses.year.reopen', $activeYear) }}"
                onsubmit="return confirm('Reopen {{ $activeYear }} for editing?');">
                @csrf
                <button type="submit" class="sheet-btn">Reopen {{ $activeYear }}</button>
            </form>
            @endif
            @endif
        </div>

        {{-- Global totals (all sheets the user can see) --}}
        <div class="stats-wrap">
            <div class="stat-card">
                <div class="stat-label">Total Debit</div>
                <div class="stat-value">IDR {{ number_format($allDebit, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Credit</div>
                <div class="stat-value">IDR {{ number_format($allCredit, 0, ',', '.') }}</div>
            </div>
        </div>

        @if (session('status'))
        <div class="sheet-alert success">{{ session('status') }}</div>
        @endif

        <div class="sheet-table-wrap">
            <table class="sheet-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Period</th>
                        <th>Owner</th>
                        <th class="right">Total Debit</th>
                        <th class="right">Total Credit</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sheets as $i => $s)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ strftime('%B', mktime(0,0,0,$s->period_month,1)) }} {{ $s->period_year }}</td>
                        <td>{{ $s->user->name }}</td>
                        <td class="right">IDR {{ number_format((int)$s->total_debit, 0, ',', '.') }}</td>
                        <td class="right">IDR {{ number_format((int)$s->total_credit, 0, ',', '.') }}</td>
                        <td>{{ $s->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($s->is_closed)
                            <a class="table-btn table-btn-light"
                                href="{{ route('expenses.show', $s) }}"
                                title="Closed sheet">
                                View (Closed)
                            </a>
                            @else
                            <a class="table-btn table-btn-light"
                                href="{{ route('expenses.show', $s) }}">
                                Open
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty">No sheets yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Sheet Modal --}}
@can('create', App\Models\ExpenseSheet::class)
<div class="modal" id="modalCreate" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-card">
        <div class="modal-head">
            <h3>Create Expense Sheet</h3>
            <button class="modal-x" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="{{ route('expenses.store') }}" class="modal-body">
            @csrf
            <div class="field-row">
                <label>Period Month</label>
                <select name="period_month" required>
                    @for ($m=1;$m<=12;$m++)
                        <option value="{{ $m }}">{{ strftime('%B', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                </select>
            </div>
            <div class="field-row">
                <label>Period Year</label>
                <input type="number" name="period_year" min="2000" max="2100" value="{{ (int)request('year', $activeYear) }}" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="sheet-btn sheet-btn-primary">Create</button>
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>
@endcan

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
        var $yearBtn   = $('#yearBtn');
        var $yearList  = $('#yearList');
        var $yearInput = $('#yearInput');
        var $yearLabel = $('#yearLabel');

        // If we don’t have the controls (single-year mode), just skip
        if ($yearBtn.length && $yearList.length && $yearInput.length && $yearLabel.length) {
            // hide list initially
            $yearList.hide();

            // toggle on button click
            $yearBtn.on('click', function (e) {
                e.stopPropagation();
                $yearList.toggle();
            });

            // click outside -> close
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#year-dd').length) {
                    $yearList.hide();
                }
            });

            // pick a year
            $yearList.on('click', '.year-dd__option', function () {
                var val = $(this).data('value');

                // update hidden + label
                $yearInput.val(val);
                $yearLabel.text(val);

                // submit the form (GET /expenses?year=...)
                $yearList.hide();
                $('#year-dd').closest('form')[0].submit();
            });
        }
    });
</script>
@endpush
@endsection