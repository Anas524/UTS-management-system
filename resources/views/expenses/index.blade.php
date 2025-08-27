@extends('layouts.app')
@section('title','Expense Sheets')

@section('content')
<div class="sheet-wrap">
    <div class="sheet-card">
        <div class="sheet-head">
            <div>
                <div class="sheet-company">PT: Universal Trade Services</div>
                <h1 class="sheet-title">Expense Sheets</h1>
            </div>
            <button class="sheet-btn sheet-btn-primary" data-modal-open="#modalCreate">+ Add Sheet</button>
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
                        <th>Created</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sheets as $i => $s)
                    <tr>
                        <td>{{ $sheets->firstItem() + $i }}</td>
                        <td>{{ strftime('%B', mktime(0,0,0,$s->period_month,1)) }} {{ $s->period_year }}</td>
                        <td>{{ $s->user->name }}</td>
                        <td>{{ $s->created_at->format('Y-m-d') }}</td>
                        <td><a class="table-link" href="{{ route('expenses.show', $s) }}">Open</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty">No sheets yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sheet-paginate">
            {{ $sheets->onEachSide(1)->links() }}
        </div>
    </div>
</div>

{{-- Create Sheet Modal --}}
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
                <input type="number" name="period_year" min="2000" max="2100" value="{{ now()->year }}" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="sheet-btn sheet-btn-primary">Create</button>
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('before-scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@endpush

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
    });
</script>
@endpush
@endsection