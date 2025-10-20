@extends('layouts.app')
@section('title','Expense Sheet')

@section('content')

@php
$isConsultant = auth()->user()?->role === 'consultant';
@endphp

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
                <strong>{{ is_null($begin) ? '–' : 'IDR '.number_format($begin,0,',','.') }}</strong>
                @can('update', $sheet)
                <button class="mini-link" data-modal-open="#modalBegin">Set</button>
                @endcan
            </div>
        </div>

        @if($isConsultant)
            <div class="muted" style="margin:.5rem 0 0 0;">Read-only mode: you can view, download, and export.</div>
        @endif

        <div class="sheet-toolbar">
            @can('update', $sheet)
            <button class="sheet-btn sheet-btn-primary" data-modal-open="#modalAddRow">+ Add Row</button>
            @endcan

            <form method="GET" action="{{ route('expenses.show', $sheet) }}" class="sort-select modern-sort" id="sortForm" style="margin-left:auto">
                <input type="hidden" name="order" id="orderInput" value="{{ $order }}">
                <div class="sort-dropdown" id="sortDropdown">
                    <button type="button" class="sort-trigger" id="sortTrigger" aria-haspopup="listbox" aria-expanded="false">
                        <span id="sortLabel">{{ $order === 'desc' ? 'Newest first' : 'Oldest first' }}</span>
                    </button>
                    <ul class="sort-menu" role="listbox" aria-labelledby="sortTrigger">
                        <li role="option" data-value="desc" class="sort-option {{ $order==='desc' ? 'is-active' : '' }}">Newest first</li>
                        <li role="option" data-value="asc" class="sort-option {{ $order==='asc'  ? 'is-active' : '' }}">Oldest first</li>
                    </ul>
                </div>
            </form>
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
                    @php $lock = $isConsultant ? 'disabled readonly class=locked-input' : ''; @endphp

                    @forelse ($rows as $i => $r)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>
                            <form method="POST" action="{{ route('expenses.rows.update', [$sheet,$r]) }}">
                                @csrf @method('PATCH')
                                <input type="date" name="date" value="{{ $r->date->format('Y-m-d') }}" {!! $lock !!}>
                        </td>
                        <td>
                            <input type="text" name="description" value="{{ $r->description }}" {!! $lock !!}>
                        </td>
                        <td>
                            <input type="text" name="doc_number" value="{{ $r->doc_number }}" {!! $lock !!}>
                        </td>
                        <td class="right">
                            <input type="text" name="debit" class="currency-input" value="{{ is_null($r->debit) ? '' : 'IDR '.number_format($r->debit,0,',','.') }}" {!! $lock !!}>
                        </td>
                        <td class="right">
                            <input type="text" name="credit" class="currency-input" value="{{ is_null($r->credit) ? '' : 'IDR '.number_format($r->credit,0,',','.') }}" {!! $lock !!}>
                        </td>
                        <td class="right">
                            <input type="text" name="amount" class="currency-input" value="{{ is_null($r->amount) ? '' : 'IDR '.number_format($r->amount,0,',','.') }}" {!! $lock !!}>
                        </td>
                        <td>
                            <input type="text" name="remarks" value="{{ $r->remarks }}" {!! $lock !!}>
                        </td>
                        <td class="right">
                            <div class="icon-actions">
                                {{-- Save (submits the PATCH form that started in Date cell) --}}
                                @can('update', $sheet)
                                <button class="icon-btn icon-save" type="submit" title="Save" aria-label="Save">
                                    <!-- check-circle -->
                                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M9 12l2 2 4-4"></path>
                                    </svg>
                                    <span class="sr-only">Save</span>
                                </button>
                                @endcan
                                </form> {{-- closes the PATCH form --}}

                                {{-- Delete --}}
                                @can('delete', $r)
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
                                @endcan

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

                                {{-- View attachments button (eye icon) opens files sequentially --}}
                                @php
                                $viewUrls = $r->attachments->map(fn($a) => route('attachments.view', $a))->values();
                                @endphp
                                <button type="button"
                                    class="icon-btn icon-view js-open-attachments"
                                    data-urls='@json($viewUrls)'
                                    title="View attachments"
                                    aria-label="View attachments">
                                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
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
                                            <span class="attach-size">{{ number_format($a->size/1024,1,',','.') }} KB</span>

                                            <a href="{{ route('attachments.download', $a) }}" class="attach-btnmini" title="Download original">Download</a>

                                            @can('delete', $r)
                                            <form method="POST" action="{{ route('attachments.destroy', [$sheet,$r,$a]) }}" class="inline-form js-confirm" data-confirm="Delete this attachment?">
                                                @csrf @method('DELETE')
                                                <button class="attach-btnmini danger" title="Delete">Delete</button>
                                            </form>
                                            @endcan
                                        </li>
                                        @empty
                                        <li class="muted">No files yet</li>
                                        @endforelse
                                    </ul>

                                    {{-- upload area: custom "Browse…" instead of native "Choose files" --}}
                                    @can('update', $sheet)
                                    <form method="POST" action="{{ route('attachments.store', [$sheet,$r]) }}" enctype="multipart/form-data" class="attach-upload">
                                        @csrf
                                        @php $inputId = 'files-'.$r->id; @endphp
                                        <input id="{{ $inputId }}" type="file" name="files[]" multiple class="attach-input-hidden">
                                        <label for="{{ $inputId }}" class="attach-browse"><span class="ico">📎</span> Browse…</label>
                                        <span class="attach-selected" data-default="No files selected">No files selected</span>
                                        <button class="attach-btn">Upload</button>
                                    </form>
                                    @endcan
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
                        <th class="right">IDR {{ number_format($totalDebit,0,',','.') }}</th>
                        <th class="right">IDR {{ number_format($totalCredit,0,',','.') }}</th>
                        <th class="right">IDR {{ number_format($totalAmount,0,',','.') }}</th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="sheet-summary">
            <div class="sum-item">
                <div class="sum-label">Beginning Balance</div>
                <div class="sum-value">{{ is_null($begin) ? '–' : 'IDR '.number_format($begin,0,',','.') }}</div>
            </div>
            <div class="sum-item">
                <div class="sum-label">Mutation</div>
                <div class="sum-value">IDR {{ number_format($mutation,0,',','.') }}</div>
            </div>
            <div class="sum-item">
                <div class="sum-label">Ending Balance</div>
                <div class="sum-value">{{ is_null($ending) ? '–' : 'IDR '.number_format($ending,0,',','.') }}</div>
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
                <input type="text" name="beginning_balance" class="currency-input"
                    value="{{ is_null($begin) ? '' : 'IDR '.number_format($begin,0,',','.') }}"
                    @if($isConsultant) disabled readonly @endif>
            </div>
            <div class="modal-actions">
                @can('update', $sheet)
                <button type="submit" class="sheet-btn sheet-btn-primary">Save</button>
                @endcan
                <button type="button" class="sheet-btn sheet-btn-ghost" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Row Modal --}}
@can('update', $sheet)
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
@endcan

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

        // View attachments in a single popup with next/prev controls and zoom
        $(document).on('click', '.js-open-attachments', function() {
            var urls = $(this).attr('data-urls');
            var $btn = $(this);

            function showTip(msg) {
                var $tip = $('<span class=\'no-attach-tip\'>' + msg + '</span>').appendTo('body');
                var off = $btn.offset();
                $tip.css({
                    top: off.top - $tip.outerHeight() - 8,
                    left: off.left + ($btn.outerWidth() - $tip.outerWidth()) / 2
                }).fadeIn(200);
                setTimeout(function() {
                    $tip.fadeOut(200, function() {
                        $(this).remove();
                    });
                }, 2000);
            }

            if (!urls) {
                showTip('This row has no attachments');
                return;
            }
            try {
                urls = JSON.parse(urls);
            } catch (e) {
                urls = [];
            }
            if (!urls.length) {
                showTip('This row has no attachments');
                return;
            }

            var win = window.open('', '_blank');
            if (!win) return;

            var popupHtml = `<!DOCTYPE html>
            <html>
                <head>
                <meta charset="utf-8">
                <title>Attachments</title>
                <style>
                body{margin:0;display:flex;flex-direction:column;height:100vh;background:#f3f4f6;overflow:hidden;}
                #viewer-wrap{flex:1;width:100%;background:#fff;display:flex;justify-content:center;align-items:center;overflow:hidden;}
                #viewer{width:100%;height:100%;border:0;transform-origin:0 0;}
                .controls{display:flex;justify-content:center;align-items:center;gap:12px;padding:10px;background:#fff;box-shadow:0 -1px 3px rgba(0,0,0,.1);}
                .controls button{background:none;border:0;padding:4px;cursor:pointer;}
                .controls button:disabled{opacity:.3;cursor:default;}
                .controls svg{width:24px;height:24px;stroke:#2563eb;stroke-width:2;fill:none;}
                </style>
                </head>
            <body>
                <div id="viewer-wrap"><iframe id="viewer"></iframe></div>
                <div class="controls">
                    <button id="prevBtn" title="Previous"><svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" /></svg></button>
                    <button id="zoomOut" title="Zoom out"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" /><path d="M8 11h6" /><path d="M21 21l-4.35-4.35" /></svg></button>
                    <span id="counter"></span>
                    <button id="zoomIn" title="Zoom in"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" /><path d="M8 11h6" /><path d="M11 8v6" /><path d="M21 21l-4.35-4.35" /></svg></button>
                    <button id="nextBtn" title="Next"><svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" /></svg></button>
                    </div>
                <script>
                    var urls = ${JSON.stringify(urls)};
                    var idx = 0;
                    var iframe = document.getElementById('viewer');
                    var counter = document.getElementById('counter');
                    var prevBtn = document.getElementById('prevBtn');
                    var nextBtn = document.getElementById('nextBtn');
                    var zoomIn = document.getElementById('zoomIn');
                    var zoomOut = document.getElementById('zoomOut');
                    var scale = 1;
                    function applyZoom(){
                    iframe.style.transform = 'scale('+scale+')';
                    }
                    function update(){
                    iframe.src = urls[idx];
                    counter.textContent = (idx+1)+' / '+urls.length;
                    prevBtn.disabled = idx===0;
                    nextBtn.disabled = idx===urls.length-1;
                    }
                    prevBtn.onclick = function(){ if(idx>0){idx--; update();} };
                    nextBtn.onclick = function(){ if(idx<urls.length-1){idx++; update();} };
                    zoomIn.onclick = function(){ if(scale<3){scale+=0.25; applyZoom();} };
                    zoomOut.onclick = function(){ if(scale>0.5){scale-=0.25; applyZoom();} };
                    update();
                    applyZoom();
                <\/script>
            </body>
            </html>`;
            win.document.write(popupHtml);
            win.document.close();
        });

        // open/close and place centered within sheet-card
        $(document).on('click', '.js-attach-toggle', function() {
            var $panel = $($(this).data('target'));
            var $others = $('.attach-panel.open').not($panel);
            $others.removeClass('open').removeData('anchor');
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
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') closeConfirmModal();
        });

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

        // Currency formatting (IDR with commas)
        function formatIDR(v) {
            v = (v || '').toString().replace(/[^0-9.-]/g, '');
            if (!v) return '';
            var n = parseInt(v, 10);
            if (isNaN(n)) return '';
            return 'IDR ' + n.toLocaleString('id-ID');
        }

        $(document).on('blur', '.currency-input', function() {
            $(this).val(formatIDR($(this).val()));
        }).on('focus', '.currency-input', function() {
            $(this).val($(this).val().replace(/IDR\s?/, '').replace(/\./g, ''));
        });

        $('form').on('submit', function() {
            $(this).find('.currency-input').each(function() {
                this.value = this.value.replace(/IDR\s?/, '').replace(/\./g, '');
            });
        });

        var $dd = $('#sortDropdown');
        var $trigger = $('#sortTrigger');
        var $menu = $dd.find('.sort-menu');
        var $label = $('#sortLabel');
        var $input = $('#orderInput');
        var $form = $('#sortForm');

        function openDD() {
            $dd.addClass('open');
            $trigger.attr('aria-expanded', 'true');
        }

        function closeDD() {
            $dd.removeClass('open');
            $trigger.attr('aria-expanded', 'false');
        }

        // Toggle open/close
        $trigger.on('click', function(e) {
            e.stopPropagation();
            $dd.hasClass('open') ? closeDD() : openDD();
        });

        // Select option
        $menu.on('click', '.sort-option', function() {
            var $opt = $(this);
            $menu.find('.sort-option').removeClass('is-active');
            $opt.addClass('is-active');

            var val = $opt.data('value');
            $label.text($.trim($opt.text()));
            $input.val(val);

            closeDD();
            $form.trigger('submit'); // submit GET form
        });

        // Click outside closes
        $(document).on('click', function(e) {
            if (!$dd.is(e.target) && $dd.has(e.target).length === 0) {
                closeDD();
            }
        });

        // Esc closes
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') closeDD();
        });
    });
</script>
@endpush
@endsection