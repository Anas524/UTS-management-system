@extends('layouts.app')
@section('title','Expense Sheet')

@section('content')
<div class="sheet-wrap">
    <div class="sheet-card">

        <div class="sheet-head">
            <div>
                <div class="sheet-company">{{ $sheet->company_name }}</div>
                <h1 class="sheet-title">Expense Sheet</h1>
                <div class="sheet-sub">
                    Period: {{ strftime('%B', mktime(0,0,0,$sheet->period_month,1)) }} {{ $sheet->period_year }}
                </div>
            </div>
            <div class="sheet-head-actions">
                <a href="{{ route('expenses.export', $sheet) }}" class="sheet-btn sheet-btn-primary">Download Excel</a>
                <a href="{{ route('expenses.index') }}" class="sheet-btn sheet-btn-outline">All Sheets</a>
                <a href="{{ route('home') }}" class="sheet-btn sheet-btn-ghost">Home</a>
            </div>
        </div>

        @if (session('status'))
        <div class="sheet-alert success" role="alert">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
        <div class="sheet-alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="sheet-meta">
            <div>Beginning Balance:
                <strong>{{ is_null($begin) ? '–' : number_format($begin,2) }}</strong>
                <button class="mini-link" data-modal-open="#modalBegin">Set</button>
            </div>
        </div>

        <div class="sheet-toolbar">
            <button class="sheet-btn sheet-btn-primary" data-modal-open="#modalAddRow">+ Add Row</button>
        </div>

        <div class="sheet-table-wrap">
            <table class="sheet-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Doc number</th>
                        <th class="right">Debit</th>
                        <th class="right">Credit</th>
                        <th class="right">Amount</th>
                        <th>Remarks</th>
                        <th class="right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $i => $r)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>
                            <form method="POST" action="{{ route('expenses.rows.update', [$sheet,$r]) }}">
                                @csrf @method('PATCH')
                                <input type="date" name="date" value="{{ $r->date->format('Y-m-d') }}">
                        </td>
                        <td>
                            <input type="text" name="description" value="{{ $r->description }}">
                        </td>
                        <td>
                            <input type="text" name="doc_number" value="{{ $r->doc_number }}">
                        </td>
                        <td class="right">
                            <input type="number" step="0.01" name="debit" value="{{ $r->debit }}">
                        </td>
                        <td class="right">
                            <input type="number" step="0.01" name="credit" value="{{ $r->credit }}">
                        </td>
                        <td class="right">
                            <input type="number" step="0.01" name="amount" value="{{ $r->amount }}">
                        </td>
                        <td>
                            <input type="text" name="remarks" value="{{ $r->remarks }}">
                        </td>
                        <td class="right">
                            <div class="icon-actions">
                                {{-- Save (submits the PATCH form that started in Date cell) --}}
                                <button class="icon-btn icon-save" type="submit" title="Save" aria-label="Save">
                                    <!-- check-circle -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M9 12l2 2 4-4"></path>
                                    </svg>
                                    <span class="sr-only">Save</span>
                                </button>
                                </form> {{-- closes the PATCH form --}}

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('expenses.rows.delete', [$sheet,$r]) }}" class="inline-form js-confirm" data-confirm="Delete this row?">
                                    @csrf @method('DELETE')
                                    <button class="icon-btn icon-del" type="submit" title="Delete" aria-label="Delete">
                                        <!-- trash-2 -->
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

                                {{-- Attachments toggle button (paperclip) stays the same --}}
                                <button type="button"
                                    class="icon-btn icon-clip js-attach-toggle"
                                    data-target="#attach-{{ $r->id }}"
                                    title="Attachments"
                                    aria-label="Attachments">
                                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                        <path d="M21.44 11.05l-8.49 8.49a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.05 17.28a2 2 0 01-2.83-2.83l8.13-8.13" />
                                    </svg>
                                </button>

                                {{-- Panel --}}
                                <div id="attach-{{ $r->id }}" class="attach-panel">
                                    <div class="attach-head">
                                        <strong>Attachments — Row {{ $i+1 }}: {{ \Illuminate\Support\Str::limit($r->description, 60) }}</strong>
                                        <div class="attach-head-actions">
                                            <a class="attach-bundle" href="{{ route('attachments.bundle', [$sheet,$r]) }}" title="Download as single PDF">
                                                <span class="ico">📄</span> Download PDF
                                            </a>
                                            <button type="button" class="attach-close" data-target="#attach-{{ $r->id }}">×</button>
                                        </div>
                                    </div>

                                    {{-- current files --}}
                                    <ul class="attach-list">
                                        @forelse($r->attachments as $a)
                                        <li>
                                            <a href="{{ route('attachments.view', $a) }}" target="_blank" class="attach-name">{{ $a->original_name }}</a>
                                            <span class="attach-size">{{ number_format($a->size/1024,1) }} KB</span>

                                            <a href="{{ route('attachments.download', $a) }}" class="attach-btnmini" title="Download original">Download</a>

                                            <form method="POST" action="{{ route('attachments.destroy', [$sheet,$r,$a]) }}" class="inline-form js-confirm" data-confirm="Delete this attachment?">
                                                @csrf @method('DELETE')
                                                <button class="attach-btnmini danger" title="Delete">Delete</button>
                                            </form>
                                        </li>
                                        @empty
                                        <li class="muted">No files yet</li>
                                        @endforelse
                                    </ul>

                                    {{-- upload area: custom "Browse…" instead of native "Choose files" --}}
                                    <form method="POST" action="{{ route('attachments.store', [$sheet,$r]) }}" enctype="multipart/form-data" class="attach-upload">
                                        @csrf
                                        @php $inputId = 'files-'.$r->id; @endphp
                                        <input id="{{ $inputId }}" type="file" name="files[]" multiple class="attach-input-hidden">
                                        <label for="{{ $inputId }}" class="attach-browse"><span class="ico">📎</span> Browse…</label>
                                        <span class="attach-selected" data-default="No files selected">No files selected</span>
                                        <button class="attach-btn">Upload</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="empty">No rows</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="right">Total</th>
                        <th class="right">{{ number_format($totalDebit,2) }}</th>
                        <th class="right">{{ number_format($totalCredit,2) }}</th>
                        <th class="right">{{ number_format($totalAmount,2) }}</th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="sheet-summary">
            <div class="sum-item">
                <div class="sum-label">Beginning Balance</div>
                <div class="sum-value">{{ is_null($begin) ? '–' : number_format($begin,2) }}</div>
            </div>
            <div class="sum-item">
                <div class="sum-label">Mutation</div>
                <div class="sum-value">0.00</div>
            </div>
            <div class="sum-item">
                <div class="sum-label">Ending Balance</div>
                <div class="sum-value">0.00</div>
            </div>
        </div>

    </div>
</div>

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
                <input type="number" step="0.01" name="beginning_balance" value="{{ $begin }}">
            </div>
            <div class="modal-actions">
                <button type="submit" class="sheet-btn sheet-btn-primary">Save</button>
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Row Modal --}}
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

@push('before-scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@endpush

@push('scripts')
<script>
    $(function() {
        let formToSubmit = null;

        // open modal
        $('[data-modal-open]').on('click', function() {
            var sel = $(this).data('modal-open');
            $(sel).addClass('open');
        });

        // close modal (button or backdrop)
        $('[data-modal-close]').on('click', function() {
            $(this).closest('.modal').removeClass('open');
        })

        // auto-hide after 3 seconds
        $('.sheet-alert').each(function() {
            var $a = $(this);
            setTimeout(function() {
                $a.addClass('is-hiding');
            }, 3000); // start fade
            setTimeout(function() {
                $a.slideUp(180, function() {
                    $(this).remove();
                });
            }, 3400); // remove from DOM
        });

        // allow manual dismiss on click (optional but its good)
        $(document).on('click', '.sheet-alert', function() {
            $(this).addClass('is-hiding').slideUp(150, function() {
                $(this).remove();
            });
        });

        function placePanelInsideCard($panel) {
            var anchor = $panel.data('anchor');
            if (!anchor) return;

            var $btn = $(anchor);
            var $card = $btn.closest('.sheet-card');
            if (!$card.length) return;

            // viewport rects
            var br = anchor.getBoundingClientRect();
            var cr = $card.get(0).getBoundingClientRect();

            // measure panel (after it is visible)
            var panelW = $panel.outerWidth() || 520;
            var gap = 8;

            // center within card, with 16px side padding
            var cardCenter = cr.left + cr.width / 2;
            var centeredLeft = cardCenter - panelW / 2;

            var minLeft = cr.left + 16;
            var maxLeft = cr.right - panelW - 16;

            var left = Math.max(minLeft, Math.min(maxLeft, centeredLeft));
            var top = br.bottom + gap; // stay just under the action bar

            $panel.css({
                left: left,
                top: top
            });
        }

        // open/close and place centered within sheet-card
        $(document).on('click', '.js-attach-toggle', function() {
            var $panel = $($(this).data('target'));
            $panel.toggleClass('open');

            if ($panel.hasClass('open')) {
                $panel.data('anchor', this);

                // ensure it's rendered before measuring width
                requestAnimationFrame(function() {
                    placePanelInsideCard($panel);
                });
            } else {
                $panel.removeData('anchor');
            }
        });

        // close button
        $(document).on('click', '.attach-close', function() {
            $($(this).data('target')).removeClass('open').removeData('anchor');
        });

        // click-outside closes any open panel
        $(document).on('click', function(e) {
            var $open = $('.attach-panel.open');
            if ($open.length && !$(e.target).closest('.attach-panel, .js-attach-toggle').length) {
                $open.removeClass('open').removeData('anchor');
            }
        });

        // keep glued on scroll/resize
        $(window).on('scroll resize', function() {
            $('.attach-panel.open').each(function() {
                placePanelInsideCard($(this));
            });
        });

        // filenames after "Browse…"
        $(document).on('change', '.attach-upload .attach-input-hidden', function() {
            var names = $.map(this.files, function(f) {
                return f.name;
            });
            var $label = $(this).closest('.attach-upload').find('.attach-selected');
            $label.text(names.length ? names.join(', ') : $label.data('default'));
        });

        // Intercept any form with .js-confirm
        $(document).on('submit', 'form.js-confirm', function(e) {
            e.preventDefault();
            formToSubmit = this;
            $('#confirmMessage').text($(this).data('confirm') || 'Are you sure?');
            openConfirmModal();
        });

        // Confirm button -> actually submit the form
        $('#confirmYes').on('click', function() {
            if (formToSubmit) {
                closeConfirmModal();
                formToSubmit.submit();
                formToSubmit = null;
            }
        });

        // Close on Esc
        $(document).on('keydown', function (e) { if (e.key === 'Escape') closeConfirmModal(); });

        // Clicking backdrop / [data-modal-close] closes (you already have this logic)
        $(document).on('click', '#modalConfirm .modal-backdrop, #modalConfirm [data-modal-close]', closeConfirmModal);

        // after your existing JS
        function openConfirmModal() {
            $('#modalConfirm').addClass('open');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmModal() {
            $('#modalConfirm').removeClass('open');
            document.body.style.overflow = '';
        }
    });
</script>
@endpush
@endsection