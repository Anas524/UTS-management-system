/* sl.js – card layout */

$(function () {
    // Prevent double-initialisation if the script is loaded twice
    if (window.__SL_INITED__) {
        return;
    }
    window.__SL_INITED__ = true;

    var $body = $('#sl-body');
    var $table = $('#sl-table'); // meta holder (for future AJAX)
    var $addBtn = $('#sl-add-row');
    var $totalEl = $('#sl-total-stock');

    if ($body.length === 0 || $table.length === 0) return;

    var nextTempId = -1; // temporary IDs for new rows
    var storeUrl = $table.data('store-url');
    var updateUrlBase = $table.data('update-url-base');
    var deleteUrlBase = $table.data('delete-url-base');
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    if (!storeUrl || !updateUrlBase || !deleteUrlBase) {
        console.warn('sl.js: Missing store/update/delete URLs on #sl-table');
    }

    // ------- Attachments (Document Hub–style modals) ----------------------
    var $attMeta = $('#sl-attachments-meta');
    var uploadUrlBase = $attMeta.data('upload-url-base');
    var listUrlBase = $attMeta.data('list-url-base');
    var downloadAllUrlBase = $attMeta.data('download-all-url-base');
    var isReadOnly = $attMeta.length ? Number($attMeta.data('read-only') || 0) === 1 : false;

    var $uploadModal = $('#att-upload-modal');
    var $viewModal = $('#att-view-modal');
    var $uploadInput = $('#att-upload-input');
    var $uploadList = $('#att-upload-list');
    var $viewerFrame = $('#att-viewer-frame');
    var $fileList = $('#att-file-list');
    var $existingList = $('#att-existing-list');
    var $dropZone = $('#att-drop-zone');

    var currentRowId = null;
    var currentUploadRowId = null;
    var currentUploadListUrl = null;

    function rowIdToUrls(rowId, $btn) {
        // Prefer per-button URLs if present, otherwise build from base
        var uploadUrl = $btn && $btn.data('upload-url')
            ? $btn.data('upload-url')
            : (uploadUrlBase ? uploadUrlBase.replace('__ID__', rowId) : null);

        var listUrl = $btn && $btn.data('list-url')
            ? $btn.data('list-url')
            : (listUrlBase ? listUrlBase.replace('__ID__', rowId) : null);

        var downloadAllUrl = $btn && $btn.data('download-all-url')
            ? $btn.data('download-all-url')
            : (downloadAllUrlBase ? downloadAllUrlBase.replace('__ID__', rowId) : '');

        return { uploadUrl, listUrl, downloadAllUrl };
    }

    function refreshFilesCount(rowId, listUrl) {
        if (!rowId || !listUrl) return;

        $.getJSON(listUrl, function (resp) {
            var list = (resp && resp.attachments) ? resp.attachments : [];
            var count = list.length;

            var $row = $('.sl-row[data-id="' + rowId + '"]');
            var $badge = $row.find('[data-files-count]');
            if ($badge.length) {
                $badge.text(count);
                $badge.toggleClass('hidden', count === 0);
            }
        });
    }

    // Disable default drag behaviour
    $(document).on('dragover drop', function (e) {
        e.preventDefault();
    });

    $dropZone.on('dragenter dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');
    });

    $dropZone.on('dragleave dragend', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');
    });

    $dropZone.on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');

        var dt = e.originalEvent.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;

        $uploadInput[0].files = dt.files;
        $uploadInput.trigger('change');
    });

    // ---------- Open upload modal ----------
    $('body').on('click', '[data-modal="att-upload"]', function () {
        var $btn = $(this);
        var rowId = $btn.data('row-id') || $btn.closest('.att-actions').data('row-id');
        if (!rowId || rowId < 0) {
            alert('Please save this row before uploading attachments.');
            return;
        }

        var urls = rowIdToUrls(rowId, $btn);
        if (!urls.uploadUrl) {
            alert('Upload URL is missing.');
            return;
        }

        currentRowId = rowId;
        currentUploadRowId = rowId;
        currentUploadListUrl = urls.listUrl;

        $('#att-upload-submit')
            .data('upload-url', urls.uploadUrl)
            .data('row-id', rowId)
            .data('list-url', urls.listUrl)
            .text('Upload')
            .prop('disabled', false);

        $uploadInput.val('');
        $uploadList.empty().append(
            '<p class="font-plus text-xs text-slate-500">No files selected yet.</p>'
        );

        loadExistingAttachments(urls.listUrl);

        $uploadModal.removeClass('hidden').addClass('flex');
    });

    // ---------- Selected files list ----------
    $uploadInput.on('change', function () {
        var files = this.files || [];
        $uploadList.empty();

        if (!files.length) {
            $uploadList.append(
                '<p class="font-plus text-xs text-slate-500">No files selected yet.</p>'
            );
            return;
        }

        $.each(files, function (i, file) {
            var sizeKB = Math.round(file.size / 1024);
            var rowHtml =
                '<div class="flex items-center justify-between rounded-xl bg-slate-900/80 px-3 py-2 text-xs text-slate-200 mb-1">' +
                '<span class="truncate max-w-xs">' + file.name + '</span>' +
                '<span class="ml-3 text-slate-400">' + sizeKB + ' KB</span>' +
                '</div>';
            $uploadList.append(rowHtml);
        });
    });

    // ---------- Submit upload (single file: "file") ----------
    $('#att-upload-submit').on('click', function () {
        var $btn = $(this);
        var uploadUrl = $btn.data('upload-url');
        var rowId = $btn.data('row-id');
        var listUrl = $btn.data('list-url');
        var files = $uploadInput[0].files;

        if (!uploadUrl) {
            alert('Upload URL is missing.');
            return;
        }
        if (!files || !files.length) {
            alert('Please choose a file.');
            return;
        }

        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('file', files[0]); // backend expects "file"

        $btn.prop('disabled', true).text('Uploading...');

        $.ajax({
            url: uploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                $uploadModal.addClass('hidden').removeClass('flex');
                if (rowId && listUrl) {
                    refreshFilesCount(rowId, listUrl);
                }
            },
            error: function () {
                alert('Upload failed. Please try again.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Upload');
            }
        });
    });

    // ---------- Viewer ----------
    $('body').on('click', '[data-modal="att-view"]', function () {
        var $btn = $(this);
        var rowId = $btn.data('row-id') || $btn.closest('.att-actions').data('row-id');
        if (!rowId || rowId < 0) {
            alert('Please save this row before viewing attachments.');
            return;
        }

        var urls = rowIdToUrls(rowId, $btn);
        if (!urls.listUrl) {
            alert('View URL is missing (data-list-url).');
            return;
        }

        currentRowId = rowId;

        $('#att-download-all').data('download-url', urls.downloadAllUrl);
        $fileList.empty().append(
            '<p class="font-plus text-xs text-slate-400">Loading attachments...</p>'
        );
        $viewerFrame.attr('src', '');

        $viewModal.removeClass('hidden').addClass('flex');

        $.getJSON(urls.listUrl, function (resp) {
            $fileList.empty();

            var list = (resp && resp.attachments) ? resp.attachments : [];

            if (!list.length) {
                $fileList.append(
                    '<p class="font-plus text-xs text-slate-400">No attachments found.</p>'
                );
                // Clear download-all URL so button shows friendly alert
                $('#att-download-all').data('download-url', '');
                return;
            }

            // If here, we have attachments → keep the URL
            $('#att-download-all').data('download-url', urls.downloadAllUrl);

            $.each(list, function (i, item) {
                var $fileBtn = $('<button type="button"></button>')
                    .addClass('block w-full text-left text-xs px-3 py-2 mb-1 rounded-lg border border-slate-700 bg-slate-900/70 text-slate-100 hover:border-sky-500 hover:text-sky-300')
                    .text(item.name || ('Attachment #' + item.id))
                    .data('preview-url', item.preview_url)
                    .data('download-url', item.download_url);

                if (i === 0 && item.preview_url) {
                    $fileBtn.addClass('ring-1 ring-sky-500');
                    $viewerFrame.attr('src', item.preview_url);
                    $('#att-download-file').data('download-url', item.download_url);
                }

                $fileList.append($fileBtn);
            });
        }).fail(function () {
            $fileList.html(
                '<p class="text-xs text-rose-400">Failed to load attachments.</p>'
            );
        });
    });

    $fileList.on('click', 'button', function () {
        var $btn = $(this);
        var previewUrl = $btn.data('preview-url');
        var downloadUrl = $btn.data('download-url') || '';

        $fileList.find('button').removeClass('ring-1 ring-sky-500');
        $btn.addClass('ring-1 ring-sky-500');

        if (previewUrl) {
            $viewerFrame.attr('src', previewUrl);
        }
        $('#att-download-file').data('download-url', downloadUrl);
    });

    $('#att-download-file').on('click', function () {
        var url = $(this).data('download-url');
        if (!url) {
            alert('Please select a file first.');
            return;
        }
        window.open(url, '_blank');
    });

    $('#att-download-all').on('click', function () {
        var url = $(this).data('download-url');
        if (!url) {
            alert('No attachments available to download.');
            return;
        }
        window.open(url, '_blank');
    });

    // ---------- Close modals ----------
    $('.att-modal-close').on('click', function () {
        $uploadModal.addClass('hidden').removeClass('flex');
        $viewModal.addClass('hidden').removeClass('flex');
    });

    // ---------- Existing attachments list (inside upload modal) ----------
    function loadExistingAttachments(listUrl) {
        if (!listUrl) {
            $existingList.empty().append(
                '<p class="font-plus text-xs text-slate-500">No attachments yet.</p>'
            );
            return;
        }

        $existingList.empty().append(
            '<p class="font-plus text-xs text-slate-400">Loading existing attachments...</p>'
        );

        $.getJSON(listUrl, function (resp) {
            $existingList.empty();

            var list = (resp && resp.attachments) ? resp.attachments : [];

            if (!list.length) {
                $existingList.append(
                    '<p class="font-plus text-xs text-slate-500">No attachments yet.</p>'
                );
                return;
            }

            $.each(list, function (i, item) {
                var dateText = item.uploaded_at ? item.uploaded_at : '';

                var rowHtml =
                    '<div class="flex items-center justify-between rounded-xl bg-slate-900/80 px-3 py-2 text-xs text-slate-100 mb-1">' +
                    '<div class="flex flex-col min-w-0">' +
                    '<span class="truncate max-w-xs font-medium">' + (item.name || ('Attachment #' + item.id)) + '</span>' +
                    (dateText
                        ? '<span class="text-[11px] text-slate-400">Uploaded on ' + dateText + '</span>'
                        : ''
                    ) +
                    '</div>' +
                    (isReadOnly
                        ? ''
                        : '<button type="button" class="att-existing-delete inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-500 hover:bg-rose-600" data-delete-url="' + item.delete_url + '">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />' +
                        '</svg>' +
                        '</button>'
                    ) +
                    '</div>';

                $existingList.append(rowHtml);
            });
        }).fail(function () {
            $existingList.html(
                '<p class="text-xs text-rose-400">Failed to load existing attachments.</p>'
            );
        });
    }

    $existingList.on('click', '.att-existing-delete', function () {
        var $btn = $(this);
        var url = $btn.data('delete-url');
        if (!url) return;

        if (!confirm('Delete this attachment?')) return;

        $.ajax({
            url: url,
            method: 'POST',
            data: { _method: 'DELETE' },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                $btn.closest('div').remove();
                refreshFilesCount(currentUploadRowId, currentUploadListUrl);
            },
            error: function () {
                alert('Failed to delete attachment.');
            }
        });
    });

    function formatMoney(val) {
        var num = Number(String(val).replace(/[^\d.-]/g, '')) || 0;
        return num.toLocaleString('en-US');
    }

    function recalcRow($row) {
        var qtyIn = Number($row.find('[data-field="qty_in"]').val() || 0);
        var qtyOut = Number($row.find('[data-field="qty_out"]').val() || 0);

        // pure math – allow negatives
        var current = qtyIn - qtyOut;

        var $current = $row.find('[data-field="current_stock"]');
        if ($current.length) {
            if (isNaN(current)) {
                $current.val('');
            } else {
                $current.val(current); // can be 0, positive, or negative
            }
        }
    }

    function recalcTotals() {
        var total = 0;
        $body.find('.sl-row').each(function () {
            var $row = $(this);
            var v = Number($row.find('[data-field="current_stock"]').val() || 0);
            total += v;
        });

        if ($totalEl.length) {
            $totalEl.text(total.toLocaleString('en-US'));
        }

        var $headerStock = $('#sl-header-stock');
        if ($headerStock.length) {
            $headerStock.text(total.toLocaleString('en-US'));
        }
    }

    function renumber() {
        var i = 1;
        $body.find('.sl-row').each(function () {
            $(this).find('[data-col="no"]').text(i++);
        });
    }

    function markDirty($row, dirty) {
        $row.attr('data-dirty', dirty ? '1' : '0');
        var $save = $row.find('.sl-save');
        if (!$save.length) return;

        if (dirty) {
            $save.removeClass('hidden');
        } else {
            $save.addClass('hidden');
        }
    }

    function snapshotRow($row) {
        return JSON.stringify(collectPayload($row));
    }

    function setOriginal($row) {
        $row.data('original', snapshotRow($row));
    }

    function checkDirty($row) {
        var original = $row.data('original');
        if (!original) {
            // no original = new row → always dirty
            markDirty($row, true);
            return;
        }
        var current = snapshotRow($row);
        markDirty($row, current !== original);
    }

    // Toggle pill styles for kg/pc and Restock
    function updatePillGroup($input) {
        var name = $input.attr('name'); // eg. unit_-1, restock_15
        if (!name) return;

        var $row = $input.closest('.sl-row');
        var selector = 'input[name="' + name.replace(/([.*+?^${}()|\[\]\/\\])/g, '\\$1') + '"]';
        var $inputs = $row.find(selector);

        $inputs.each(function () {
            var $i = $(this);
            var $label = $i.closest('label');

            if (this.checked) {
                $label
                    .addClass('bg-white text-slate-900 shadow-sm')
                    .removeClass('text-slate-500');
            } else {
                $label
                    .removeClass('bg-white text-slate-900 shadow-sm')
                    .addClass('text-slate-500');
            }
        });
    }

    // Collect all fields from a row into payload for controller
    function collectPayload($row) {
        var payload = {};

        $row.find('[data-field]').each(function () {
            var $el = $(this);
            var field = $el.data('field');

            // radios: only send the checked one
            if ($el.attr('type') === 'radio' && !$el.prop('checked')) {
                return;
            }

            var val = $el.val();

            // clean numeric formats so Laravel validator passes
            if (field === 'unit_price') {
                val = String(val || '').replace(/[^\d.-]/g, '');
            }

            payload[field] = val;
        });

        return payload;
    }

    function toggleSaveBusy($row, busy) {
        var $saveBtn = $row.find('.sl-save');
        if (!$saveBtn.length) return;

        var $label = $saveBtn.find('.sl-save-label');
        var $spinner = $saveBtn.find('.sl-save-spinner');

        if (busy) {
            $row.data('saving', 1);
            $saveBtn.prop('disabled', true);
            $label.addClass('opacity-60');
            $spinner.removeClass('hidden');
        } else {
            $row.data('saving', 0);
            $saveBtn.prop('disabled', false);
            $label.removeClass('opacity-60');
            $spinner.addClass('hidden');
        }
    }

    function saveRowAjax($row) {
        var id = $row.data('id');
        var isNew = !id || id < 0;

        // --- prevent double save on this row ---
        if ($row.data('saving') === 1) {
            return; // already saving, ignore extra clicks
        }

        toggleSaveBusy($row, true);

        var payload = collectPayload($row);

        var url, method, data;
        if (isNew) {
            url = storeUrl;
            method = 'POST';
            data = payload;
        } else {
            url = updateUrlBase.replace('__ID__', id);
            method = 'POST'; // POST + _method=PUT
            data = $.extend({ _method: 'PUT' }, payload);
        }

        $.ajax({
            url: url,
            method: method,
            data: data,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (res) {
                if (res && res.entry) {
                    // attach real ID for newly created rows
                    $row.attr('data-id', res.entry.id);

                    // make sure attachment buttons now use real ID
                    $row.find('.att-actions').attr('data-row-id', res.entry.id);
                    $row.find('[data-modal="att-upload"], [data-modal="att-view"]')
                        .attr('data-row-id', res.entry.id);

                    // update current stock from server, trim trailing zeros
                    if (typeof res.entry.current_stock !== 'undefined' && res.entry.current_stock !== null) {
                        var cs = String(res.entry.current_stock);
                        cs = cs.replace(/\.?0+$/, ''); // 200.0000 -> 200, 0.0000 -> 0
                        $row.find('[data-field="current_stock"]').val(cs);
                    }
                }

                // after a successful save, current state becomes new "original"
                setOriginal($row);
                markDirty($row, false);
                recalcTotals();
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                if (xhr.status === 422) {
                    alert('Failed to save row. Please check the Item field.');
                } else {
                    alert('Failed to save row. Please try again.');
                }
            },
            complete: function () {
                // unlock row + hide spinner
                toggleSaveBusy($row, false);
            }
        });
    }

    function deleteRowAjax($row) {
        var id = $row.data('id');

        // Unsaved row: just remove
        if (!id || id < 0) {
            $row.remove();
            renumber();
            recalcTotals();
            return;
        }

        if (!confirm('Delete this row?')) return;

        var url = deleteUrlBase.replace('__ID__', id);

        $.ajax({
            url: url,
            method: 'POST', // POST + _method=DELETE
            data: { _method: 'DELETE' },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            complete: function () {
                // even if error, remove from UI to avoid ghost rows
                $row.remove();
                renumber();
                recalcTotals();
            }
        });
    }

    function attachRowEvents($row) {
        // snapshot original values for existing rows
        if (!$row.data('original')) {
            setOriginal($row);
        }

        // Inputs & textareas
        $row.find('input, textarea, select').each(function () {
            var $el = $(this);

            $el.on('input change', function () {
                var field = $el.data('field');

                if (field === 'qty_in' || field === 'qty_out') {
                    recalcRow($row);
                    recalcTotals();
                }

                // Pill radios
                if ($el.attr('type') === 'radio' &&
                    (field === 'unit' || field === 'restock')) {
                    updatePillGroup($el);
                }

                // decide dirty based on diff vs original snapshot
                checkDirty($row);
            });

            if ($el.data('field') === 'unit_price') {
                $el.on('blur', function () {
                    var formatted = formatMoney($el.val());
                    $el.val(formatted || '');
                    checkDirty($row);
                });
            }
        });

        // Initial pill sync (for server-rendered rows)
        $row.find('input[type="radio"][data-field="unit"], input[type="radio"][data-field="restock"]')
            .each(function () {
                updatePillGroup($(this));
            });

        // SAVE (call AJAX)
        var $saveBtn = $row.find('.sl-save');
        if ($saveBtn.length) {
            $saveBtn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                saveRowAjax($row);
            });
        }

        // DELETE (call AJAX)
        var $deleteBtn = $row.find('.sl-delete');
        if ($deleteBtn.length) {
            $deleteBtn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                deleteRowAjax($row);
            });
        }

        // TOGGLE details when clicking summary (but not actions)
        $row.find('.sl-summary-main').on('click', function () {
            var $details = $row.find('.sl-details');
            var $chevron = $row.find('.sl-chevron');
            var isOpen = $details.is(':visible');

            $details.slideToggle(150);
            $chevron.toggleClass('rotate-180');

            if (!isOpen) {
                $chevron
                    .removeClass('bg-slate-100 text-slate-500')
                    .addClass('bg-slate-900 text-white');
            } else {
                $chevron
                    .removeClass('bg-slate-900 text-white')
                    .addClass('bg-slate-100 text-slate-500');
            }
        });
    }

    function createRow() {
        var tempId = nextTempId;
        nextTempId--;

        var html = `
    <div class="sl-row rounded-2xl border border-slate-200 bg-white/95 shadow-sm px-3 py-4 md:px-4 md:py-5 space-y-3"
         data-id="${tempId}"
         data-dirty="1">

        <!-- SUMMARY BAR -->
        <div class="sl-summary flex items-center justify-between gap-3">
            <div class="sl-summary-main flex-1 flex flex-wrap items-center gap-3 text-[11px] text-slate-500 cursor-pointer">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-white text-[10px]" data-col="no"></span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-slate-400">•</span>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Item code</label>
                        <input type="text"
                            data-field="item"
                            class="w-[220px] md:w-[260px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] text-slate-800 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 ml-auto">
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Qty in</label>
                        <input type="number"
                            data-field="qty_in"
                            min="0"
                            step="1"
                            class="w-[120px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] text-right focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Qty out</label>
                        <input type="number"
                            data-field="qty_out"
                            min="0"
                            step="1"
                            class="w-[120px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] text-right focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Current</label>
                        <input type="number"
                            data-field="current_stock"
                            readonly
                            class="w-[120px] rounded-full border border-slate-100 bg-slate-50 px-3 py-1.5 text-[11px] text-right text-slate-700">
                    </div>
                </div>

                <span class="sl-chevron ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[9px] text-slate-500 transition-transform">
                    ▾
                </span>
            </div>

            <div class="inline-flex items-center gap-1 sl-actions">
                <button type="button"
                        class="sl-save rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-semibold text-white shadow-sm hover:bg-emerald-600">
                    <span class="sl-save-label">Save</span>
                    <span class="sl-save-spinner hidden ml-1 inline-block h-3 w-3 border-2 border-white/60 border-t-transparent rounded-full align-middle animate-spin"></span>
                </button>
                <button type="button"
                        class="sl-delete rounded-full bg-rose-50 px-3 py-1 text-[10px] font-semibold text-rose-600 border border-rose-100 hover:bg-rose-100">
                    Delete
                </button>

                <div class="flex items-center gap-2 ml-1 att-actions" data-row-id="${tempId}">
                    <!-- Upload (only meaningful after save, id < 0 will be blocked in JS) -->
                    <button
                        type="button"
                        title="Upload attachments"
                        class="slatt-upload-btn inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        data-modal="att-upload"
                        data-row-id="${tempId}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.5 15.75v2.25A2.25 2.25 0 006.75 20.25h10.5A2.25 2.25 0 0019.5 18v-2.25M12 4.5v11.25m0 0l-3.75-3.75M12 15.75l3.75-3.75" />
                        </svg>
                    </button>

                    <!-- View -->
                    <button
                        type="button"
                        title="View attachments"
                        class="slatt-view-btn inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        data-modal="att-view"
                        data-row-id="${tempId}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                            <circle cx="12" cy="12" r="3.25" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- DETAILS (collapsed) -->
        <div class="sl-details mt-3 space-y-6 text-[11px] hidden">
            <div class="flex flex-wrap gap-10">
                <div class="w-full md:w-[420px]">
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Description</label>
                    <textarea
                        data-field="description"
                        class="w-[300px] md:w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] resize-y min-h-[2.75rem] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                        placeholder="Item description"></textarea>
                </div>

                <div class="w-full md:w-[340px]">
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Unit price (exc. PPN)</label>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-slate-400">IDR</span>
                        <input type="text"
                            data-field="unit_price"
                            class="w-[280px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] text-right focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                            placeholder="1,000,000">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-10">
                <div class="w-full md:w-[340px]">
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Vendor</label>
                    <input type="text"
                        data-field="vendor"
                        class="w-[300px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
                        placeholder="Vendor name">
                </div>

                <div class="w-full md:w-[340px]">
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Sales channel</label>
                    <input type="text"
                        data-field="sales_channel"
                        class="w-[300px] md:w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400">
                </div>
            </div>

            <div class="mt-2 grid gap-3 md:grid-cols-4 text-[11px]">
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Date in (Received)</label>
                    <input type="date"
                        data-field="date_in"
                        class="w-full max-w-[220px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] text-center focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Date out (Sale)</label>
                    <input type="date"
                        data-field="date_out"
                        class="w-full max-w-[220px] rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] text-center focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Unit</label>
                    <div class="inline-flex rounded-full bg-slate-100 p-0.5 text-[11px]" role="group">
                        <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium bg-white text-slate-900 shadow-sm">
                            <input type="radio" class="sr-only" name="unit_${tempId}" data-field="unit" value="kg" checked>
                            <span>kg</span>
                        </label>
                        <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium text-slate-500">
                            <input type="radio" class="sr-only" name="unit_${tempId}" data-field="unit" value="pc">
                            <span>pc</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">Restock</label>
                    <div class="inline-flex rounded-full bg-slate-100 p-0.5 text-[11px]" role="group">
                        <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium bg-white text-slate-900 shadow-sm">
                            <input type="radio" class="sr-only" name="restock_${tempId}" data-field="restock" value="no" checked>
                            <span>No</span>
                        </label>
                        <label class="cursor-pointer inline-flex items-center justify-center px-3 py-0.5 rounded-full font-medium text-slate-500">
                            <input type="radio" class="sr-only" name="restock_${tempId}" data-field="restock" value="yes">
                            <span>Yes</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;

        var $row = $(html);
        $body.append($row);
        renumber();
        recalcRow($row);
        recalcTotals();
        attachRowEvents($row);
    }

    // Attach to existing rows
    $body.find('.sl-row').each(function () {
        attachRowEvents($(this));
    });
    renumber();
    recalcTotals();

    // Add row button – safe binding (no double rows)
    var isAddingRow = false;

    if ($addBtn.length) {
        $addBtn.off('click.sl').on('click.sl', function (e) {
            e.preventDefault();

            // guard against double-fires / double-clicks
            if (isAddingRow) return;
            isAddingRow = true;

            createRow();

            // allow next click (next tick is enough)
            setTimeout(function () {
                isAddingRow = false;
            }, 0);
        });
    }
});
