@extends('layouts.app')

@section('title', 'Document Hub')

@section('content')
<section class="py-10 px-4">
    <div class="max-w-6xl mx-auto font-plus text-dec-none space-y-6">

        {{-- Header / hero ---------------------------------------------------- --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    ← Back to dashboard
                </a>

                <h1 class="mt-3 text-xl md:text-2xl font-semibold text-slate-900">
                    Document Hub
                </h1>
                <p class="mt-1 text-xs text-slate-500 max-w-xl">
                    Organize shared documents into folders by month so consultants and admins can find them quickly.
                </p>
            </div>

            @if (! $isConsultant)
            <button
                type="button"
                id="dh-open-create-modal"
                class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-sky-600">
                + Add New Record
            </button>
            @endif
        </div>

        {{-- Folder cards ----------------------------------------------------- --}}
        @if ($folders->count())
        {{-- Folder grid ------------------------------------------------------------ --}}
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            @foreach($folders as $folder)
            <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-sky-300 hover:shadow-md transition-all">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3">
                        {{-- Folder icon circle --}}
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-50 text-sky-500 group-hover:bg-sky-100">
                            <span class="text-lg">📁</span>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                {{ $folder->folder_name }}
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Latest: {{ $folder->latest_month }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                    <span>{{ $folder->months_count }} month(s)</span>
                    <span>{{ $folder->attachments_sum }} file(s)</span>
                </div>

                <div class="mt-4 flex justify-end">
                    <a href="{{ route('dh.folder', $folder->slug) }}"
                        class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-800">
                        Open
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        {{-- Empty state --------------------------------------------------- --}}
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-6 py-10 text-center text-xs text-slate-500">
            <p class="mb-1">No folders yet.</p>
            @if (! $isConsultant)
            <p>
                Click
                <button
                    type="button"
                    id="dh-open-create-modal-empty"
                    class="inline-flex items-center gap-1 rounded-full bg-sky-500 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-sky-600">
                    + Add New Record
                </button>
                to create one.
            </p>
            @endif
        </div>
        @endif

    </div>
</section>

{{-- Create / Edit folder modal --------------------------------------------- --}}
@if (! $isConsultant)
<div id="dh-folder-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    {{-- Inner content wrapper: shared width for title + inputs + buttons --}}
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-6">
        <h3 id="dh-folder-modal-title" class="text-sm font-semibold text-slate-900 mb-4">
            New Record
        </h3>

        <form id="dh-folder-form" method="POST" action="{{ route('dh.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="dh-folder-method" value="POST">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Folder name
                </label>
                <input
                    type="text"
                    name="folder_name"
                    class="w-[420px] rounded-full border border-slate-300 px-3 py-2 text-xs focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                    required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Month
                </label>
                <input
                    type="text"
                    name="month_label"
                    placeholder="e.g. Nov 2025"
                    class="w-[420px] rounded-full border border-slate-300 px-3 py-2 text-xs focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
            </div>

            <div class="mt-5 flex items-center justify-end gap-2 text-xs">
                <button
                    type="button"
                    id="dh-folder-cancel"
                    class="rounded-full bg-slate-100 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-200">
                    Cancel
                </button>
                <button
                    type="submit"
                    id="dh-folder-submit"
                    class="rounded-full bg-sky-500 px-4 py-2 font-semibold text-white hover:bg-sky-600">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@if (! $isConsultant)
@push('scripts')
<script>
    $(function() {
        var $modal = $('#dh-folder-modal');
        var $form = $('#dh-folder-form');
        var $title = $('#dh-folder-modal-title');
        var $method = $('#dh-folder-method');

        function openModal() {
            $modal.removeClass('hidden').addClass('flex');
        }

        function closeModal() {
            $modal.addClass('hidden').removeClass('flex');
        }

        // Open "New Record" modal (from main button)
        $('#dh-open-create-modal').on('click', function() {
            $title.text('New Record');
            $form.attr('action', "{{ route('dh.store') }}");
            $method.val('POST');
            $form[0].reset();
            openModal();
        });

        // Open "New Record" modal from empty-state button
        $('#dh-open-create-modal-empty').on('click', function() {
            $('#dh-open-create-modal').trigger('click');
        });

        // Open "Edit" modal with existing values
        $('.dh-edit-folder').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name') || '';
            var month = $(this).data('month') || '';

            $title.text('Edit folder');
            $form.attr('action', "{{ url('document-hub') }}/" + id);
            $method.val('PUT');

            $form.find('input[name="folder_name"]').val(name);
            $form.find('input[name="month_label"]').val(month);

            openModal();
        });

        $('#dh-folder-cancel').on('click', closeModal);

        // Close on ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // Close when clicking overlay
        $modal.on('click', function(e) {
            if (e.target === this) closeModal();
        });
    });
</script>
@endpush
@endif