@extends('layouts.app')

@section('title', 'Document Hub – Folder')

@section('content')
@php
// Fallbacks so it still works if you haven’t added new columns yet
$folderName = $entry->folder_name ?? $entry->description ?? 'Untitled folder';
$monthLabel = $entry->month_label ?? $entry->remarks ?? optional($entry->created_at)->format('M Y');
$folderSlug = rawurlencode($folderName);
@endphp

<section class="py-10 px-4">
    <div class="max-w-6xl mx-auto font-plus text-dec-none">

        {{-- Header ------------------------------------------------------------ --}}
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dh.index') }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        ← Back to Document Hub
                    </a>
                    <a href="{{ route('dh.folder', $folderSlug) }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        ← Back to Months
                    </a>
                </div>

                <h1 class="mt-3 text-xl md:text-2xl font-semibold text-slate-900">
                    {{ $folderName }}
                </h1>
                <p class="mt-1 text-xs text-slate-500">
                    Month: <span class="font-semibold">{{ $monthLabel }}</span>
                </p>
                <p class="mt-1 text-[11px] text-slate-400 max-w-xl">
                    Upload and review all supporting documents for this folder. Consultants can only view files.
                </p>
            </div>

            <div class="text-right text-xs text-slate-500">
                <p class="uppercase tracking-[0.16em] mb-1">Created on</p>
                <p class="text-sm font-semibold text-slate-900">
                    {{ optional($entry->created_at)->format('d-m-Y') ?? '-' }}
                </p>
            </div>
        </div>

        {{-- Single record table (same style as old index) -------------------- --}}
        <div class="overflow-hidden rounded-2xl bg-slate-900/90 border border-slate-700 shadow-xl">
            <table class="min-w-full table-fixed divide-y divide-slate-800 text-xs">
                <colgroup>
                    <col class="w-28"> {{-- Date --}}
                    <col class="w-[58%]"> {{-- Description (wider) --}}
                    <col class="w-20"> {{-- Files --}}
                    <col class="w-32"> {{-- Actions (narrower) --}}
                </colgroup>

                <thead class="bg-slate-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-300">Description</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-300">Files</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-300">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-800">
                    <tr class="hover:bg-slate-900/60 transition-colors" data-row-id="{{ $entry->id }}">
                        {{-- Date --}}
                        <td class="px-4 py-3 text-slate-300 align-top whitespace-nowrap">
                            {{ optional($entry->created_at)->format('d-m-Y') ?? '-' }}
                        </td>

                        {{-- Description textarea --}}
                        <td class="py-3 align-top">
                            <div class="px-4">
                                <textarea
                                    class="sa-textarea block w-[600px] mx-auto resize-y rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:opacity-60 disabled:cursor-not-allowed"
                                    rows="3"
                                    data-original="{{ $entry->description }}"
                                    data-field="description"
                                    @if($isConsultant) disabled @endif>{{ $entry->description }}</textarea>
                            </div>
                        </td>

                        {{-- Files count --}}
                        <td class="px-4 py-3 text-center align-top">
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-800 px-2.5 py-1 text-[11px] text-slate-50" data-files-count>
                                {{ $entry->attachments_count ?? 0 }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center justify-end gap-2 text-[11px]">
                                @if (! $isConsultant)
                                <button
                                    type="button"
                                    title="Save changes"
                                    class="sa-save-btn hidden inline-flex items-center gap-1 rounded-full bg-emerald-500/90 px-3 py-1 font-semibold text-white hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                    data-update-url="{{ route('dh.update', $entry) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Save
                                </button>
                                @endif

                                <div
                                    class="flex items-center gap-2 att-actions"
                                    data-row-id="{{ $entry->id }}">
                                    @if (! $isConsultant)
                                    {{-- Upload --}}
                                    <button
                                        type="button"
                                        title="Upload attachments"
                                        class="inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                        data-modal="att-upload"
                                        data-upload-url="{{ route('dh.attachments.store', $entry) }}"
                                        data-list-url="{{ route('dh.attachments.index', $entry) }}"
                                        data-row-id="{{ $entry->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.5 15.75v2.25A2.25 2.25 0 006.75 20.25h10.5A2.25 2.25 0 0019.5 18v-2.25M12 4.5v11.25m0 0l-3.75-3.75M12 15.75l3.75-3.75" />
                                        </svg>
                                    </button>
                                    @endif

                                    {{-- View --}}
                                    <button
                                        type="button"
                                        title="View attachments"
                                        class="inline-flex items-center justify-center rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1.5 text-slate-200 hover:border-sky-500 hover:text-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                        data-modal="att-view"
                                        data-row-id="{{ $entry->id }}"
                                        data-list-url="{{ route('dh.attachments.index', $entry) }}"
                                        data-download-all-url="{{ route('dh.attachments.downloadAll', $entry) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                            <circle cx="12" cy="12" r="3.25" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</section>

{{-- Upload modal (same as before) ------------------------------------------ --}}
<div
    id="att-upload-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="w-full max-w-3xl rounded-2xl bg-slate-950 text-slate-100 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-sm font-semibold">Upload Attachments</h3>
            <button
                type="button"
                title="Close"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-100 hover:bg-white/20">
                ✕
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">
            <p class="font-plus text-xs text-slate-400">
                PDF and images only, max 25MB each.
            </p>

            <div
                id="att-drop-zone"
                class="border border-dashed border-slate-700 rounded-xl bg-slate-900/70 px-6 py-10 text-center text-xs text-slate-400">
                <p class="font-plus mb-3 font-medium text-slate-200">
                    Drag &amp; drop files here
                </p>
                <p class="font-plus">or</p>
                <label
                    title="Select files from your computer"
                    class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-900 hover:bg-white">
                    Browse files
                    <input id="att-upload-input" type="file" class="hidden" multiple>
                </label>
            </div>

            <div id="att-upload-list" class="space-y-2 text-xs">
                {{-- JS: show pending files here --}}
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800">
                <h4 class="mb-2 text-xs font-semibold text-slate-200">
                    Existing attachments
                </h4>
                <div id="att-existing-list" class="space-y-2 text-xs">
                    {{-- JS: existing files with delete --}}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-800 px-6 py-4">
            <button
                type="button"
                class="att-modal-close rounded-full border border-slate-600 bg-slate-900 px-4 py-2 text-xs font-semibold text-slate-100 hover:bg-slate-800">
                Cancel
            </button>
            <button
                type="button"
                id="att-upload-submit"
                class="rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white hover:bg-sky-600 disabled:opacity-60"
                data-upload-url=""
                data-row-id="">
                Upload
            </button>
        </div>
    </div>
</div>

{{-- Viewer modal ----------------------------------------------------------- --}}
<div
    id="att-view-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-slate-950 text-slate-100 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-sm font-semibold">Attachments Viewer</h3>
            <button
                type="button"
                title="Close viewer"
                class="att-modal-close inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-100 hover:bg-white/20">
                ✕
            </button>
        </div>

        <div class="flex h-[540px]">
            <div class="w-64 border-r border-slate-800 bg-slate-950/80 py-4 px-6 space-y-2 text-xs" id="att-file-list">
                {{-- JS: file buttons --}}
            </div>

            <div class="flex-1 bg-slate-900/80">
                <iframe
                    id="att-viewer-frame"
                    class="h-full w-full border-0"
                    src=""></iframe>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-800 px-6 py-3 text-xs">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="att-download-file"
                    title="Download selected file"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-600 bg-slate-900 px-3 py-1.5 font-semibold text-slate-100 hover:bg-slate-800"
                    data-download-url="">
                    Download
                </button>

                <button
                    type="button"
                    id="att-download-all"
                    title="Download all attachments as ZIP"
                    class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-3 py-1.5 font-semibold text-white hover:bg-sky-600"
                    data-download-url="">
                    Download All
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        // === 1) Show / hide "Save" button when textarea changes ============
        $('body').on('input', '.sa-textarea', function() {
            var $row = $(this).closest('tr[data-row-id]');
            var $saveBtn = $row.find('.sa-save-btn');
            if (!$saveBtn.length) return;

            var changed = false;
            $row.find('.sa-textarea').each(function() {
                var original = $(this).attr('data-original') || '';
                if ($.trim($(this).val()) !== $.trim(original)) {
                    changed = true;
                }
            });

            if (changed) {
                $saveBtn.removeClass('hidden');
            } else {
                $saveBtn.addClass('hidden');
            }
        });

        // === 2) Save description + remarks via AJAX =======================
        $('body').on('click', '.sa-save-btn', function() {
            var $btn = $(this);
            var url = $btn.data('update-url');
            if (!url) {
                alert('Update URL missing.');
                return;
            }

            var $row = $btn.closest('tr[data-row-id]');
            var payload = {
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
            };

            $row.find('.sa-textarea').each(function() {
                var field = $(this).data('field');
                payload[field] = $(this).val();
            });

            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: payload,
                success: function() {
                    $row.find('.sa-textarea').each(function() {
                        $(this).attr('data-original', $(this).val());
                    });
                    $btn.addClass('hidden');
                },
                error: function() {
                    alert('Failed to save changes. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Save');
                }
            });
        });

        // === 3) Modals: Upload & Viewer ===================================
        var $uploadModal = $('#att-upload-modal');
        var $viewModal = $('#att-view-modal');
        var $uploadInput = $('#att-upload-input');
        var $uploadList = $('#att-upload-list');
        var $viewerFrame = $('#att-viewer-frame');
        var $fileList = $('#att-file-list');
        var $existingList = $('#att-existing-list');

        var currentRowId = null;
        var currentUploadRowId = null;
        var currentUploadListUrl = null;

        function refreshFilesCount(rowId, listUrl) {
            if (!rowId || !listUrl) return;

            $.getJSON(listUrl, function(resp) {
                var count = (resp && resp.items) ? resp.items.length : 0;
                var $row = $('tr[data-row-id="' + rowId + '"]');
                $row.find('[data-files-count]').text(count);
            });
        }

        // Drag + drop
        var $dropZone = $('#att-drop-zone');

        $(document).on('dragover drop', function(e) {
            e.preventDefault();
        });

        $dropZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');
        });

        $dropZone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');
        });

        $dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('ring-2 ring-sky-500 ring-offset-2 ring-offset-slate-900');

            var dt = e.originalEvent.dataTransfer;
            if (!dt || !dt.files || !dt.files.length) return;

            $uploadInput[0].files = dt.files;
            $uploadInput.trigger('change');
        });

        // Open upload modal
        $('body').on('click', '[data-modal="att-upload"]', function() {
            var $btn = $(this);
            currentRowId = $btn.data('row-id');
            var uploadUrl = $btn.data('upload-url');
            var listUrl = $btn.data('list-url');

            currentUploadRowId = currentRowId;
            currentUploadListUrl = listUrl;

            $('#att-upload-submit')
                .data('upload-url', uploadUrl)
                .data('row-id', currentRowId)
                .data('list-url', listUrl)
                .text('Upload')
                .prop('disabled', false);

            $uploadInput.val('');
            $uploadList.empty().append(
                '<p class="font-plus text-xs text-slate-500">No files selected yet.</p>'
            );

            loadExistingAttachments(listUrl);

            $uploadModal.removeClass('hidden').addClass('flex');
        });

        // List selected files
        $uploadInput.on('change', function() {
            var files = this.files || [];
            $uploadList.empty();

            if (!files.length) {
                $uploadList.append(
                    '<p class="font-plus text-xs text-slate-500">No files selected yet.</p>'
                );
                return;
            }

            $.each(files, function(i, file) {
                var sizeKB = Math.round(file.size / 1024);
                var rowHtml =
                    '<div class="flex items-center justify-between rounded-xl bg-slate-900/80 px-3 py-2 text-xs text-slate-200 mb-1">' +
                    '<span class="truncate max-w-xs">' + file.name + '</span>' +
                    '<span class="ml-3 text-slate-400">' + sizeKB + ' KB</span>' +
                    '</div>';
                $uploadList.append(rowHtml);
            });
        });

        // Submit upload
        $('#att-upload-submit').on('click', function() {
            var $btn = $(this);
            var uploadUrl = $btn.data('upload-url');
            var rowId = $btn.data('row-id');
            var listUrl = $btn.data('list-url');
            var files = $uploadInput[0].files;

            if (!uploadUrl) {
                alert('Upload URL is missing (data-upload-url).');
                return;
            }
            if (!files || !files.length) {
                alert('Please choose at least one file.');
                return;
            }

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('row_id', rowId);
            $.each(files, function(i, file) {
                formData.append('files[]', file);
            });

            $btn.prop('disabled', true).text('Uploading...');

            $.ajax({
                url: uploadUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    $uploadModal.addClass('hidden').removeClass('flex');
                    refreshFilesCount(rowId, listUrl);
                },
                error: function() {
                    alert('Upload failed. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Upload');
                }
            });
        });

        // === 4) Viewer =====================================================
        $('body').on('click', '[data-modal="att-view"]', function() {
            var $btn = $(this);
            currentRowId = $btn.data('row-id');
            var listUrl = $btn.data('list-url');
            var downloadAllUrl = $btn.data('download-all-url');

            if (!listUrl) {
                alert('View URL is missing (data-list-url).');
                return;
            }

            $('#att-download-all').data('download-url', downloadAllUrl || '');
            $fileList.empty().append(
                '<p class="font-plus text-xs text-slate-400">Loading attachments...</p>'
            );
            $viewerFrame.attr('src', '');

            $viewModal.removeClass('hidden').addClass('flex');

            $.getJSON(listUrl, function(resp) {
                $fileList.empty();

                if (!resp || !resp.items || !resp.items.length) {
                    $fileList.append(
                        '<p class="font-plus text-xs text-slate-400">No attachments found.</p>'
                    );
                    return;
                }

                $.each(resp.items, function(i, item) {
                    var $fileBtn = $('<button type="button"></button>')
                        .addClass('block w-full text-left text-xs px-3 py-2 mb-1 rounded-lg border border-slate-700 bg-slate-900/70 text-slate-100 hover:border-sky-500 hover:text-sky-300')
                        .text(item.name)
                        .data('preview-url', item.preview_url)
                        .data('download-url', item.download_url);

                    if (i === 0 && item.preview_url) {
                        $fileBtn.addClass('ring-1 ring-sky-500');
                        $viewerFrame.attr('src', item.preview_url);
                    }

                    $fileList.append($fileBtn);
                });
            }).fail(function() {
                $fileList.html(
                    '<p class="text-xs text-rose-400">Failed to load attachments.</p>'
                );
            });
        });

        $fileList.on('click', 'button', function() {
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

        $('#att-download-file').on('click', function() {
            var url = $(this).data('download-url');
            if (!url) {
                alert('Please select a file first.');
                return;
            }
            window.open(url, '_blank');
        });

        $('#att-download-all').on('click', function() {
            var url = $(this).data('download-url');
            if (!url) {
                alert('Download-all URL is missing.');
                return;
            }
            window.open(url, '_blank');
        });

        // === 5) Close modals ==============================================
        $('.att-modal-close').on('click', function() {
            $uploadModal.addClass('hidden').removeClass('flex');
            $viewModal.addClass('hidden').removeClass('flex');
        });

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

            $.getJSON(listUrl, function(resp) {
                $existingList.empty();

                if (!resp || !resp.items || !resp.items.length) {
                    $existingList.append(
                        '<p class="font-plus text-xs text-slate-500">No attachments yet.</p>'
                    );
                    return;
                }

                $.each(resp.items, function(i, item) {
                    var dateText = item.uploaded_at ? item.uploaded_at : '';

                    var rowHtml =
                        '<div class="flex items-center justify-between rounded-xl bg-slate-900/80 px-3 py-2 text-xs text-slate-100 mb-1">' +
                        '<div class="flex flex-col min-w-0">' +
                        '<span class="truncate max-w-xs font-medium">' + item.name + '</span>' +
                        (dateText ?
                            '<span class="text-[11px] text-slate-400">Uploaded on ' + dateText + '</span>' :
                            ''
                        ) +
                        '</div>' +
                        '<button type="button" class="att-existing-delete inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-500 hover:bg-rose-600" data-delete-url="' + item.delete_url + '">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />' +
                        '</svg>' +
                        '</button>' +
                        '</div>';

                    $existingList.append(rowHtml);
                });
            }).fail(function() {
                $existingList.html(
                    '<p class="text-xs text-rose-400">Failed to load existing attachments.</p>'
                );
            });
        }

        $existingList.on('click', '.att-existing-delete', function() {
            var $btn = $(this);
            var url = $btn.data('delete-url');
            if (!url) return;

            if (!confirm('Delete this attachment?')) return;

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function() {
                    $btn.closest('div').remove();
                    refreshFilesCount(currentUploadRowId, currentUploadListUrl);
                },
                error: function() {
                    alert('Failed to delete attachment.');
                }
            });
        });
    });
</script>
@endpush