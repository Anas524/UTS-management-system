@extends('layouts.app')

@section('title', 'Document Hub • ' . $folderName)

@section('content')
<section class="py-10 px-4">
    <div class="text-dec-none font-plus max-w-6xl mx-auto font-plus text-dec-none space-y-6">

        {{-- Header ----------------------------------------------------------- --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('dh.index') }}"
                   class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    ← Back to Document Hub
                </a>

                <h1 class="mt-3 text-xl md:text-2xl font-semibold text-slate-900">
                    {{ $folderName }}
                </h1>
                <p class="mt-1 text-xs text-slate-500 max-w-xl">
                    Select a month to manage its files.
                </p>
            </div>

            @if (! $isConsultant)
                {{-- Optional: reuse "Add New Record" to add another month for this folder --}}
                <button
                    type="button"
                    id="dh-open-create-modal"
                    class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-sky-600">
                    + Add Month
                </button>
            @endif
        </div>

        {{-- Month cards ------------------------------------------------------ --}}
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            @forelse($months as $entry)
                <a href="{{ route('dh.show', $entry) }}"
                   class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm hover:border-sky-300 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-50 text-sky-500 group-hover:bg-sky-100">
                            📂
                        </div>
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                Month
                            </div>
                            <div class="text-sm font-semibold text-slate-900">
                                {{ $entry->month_label ?? $entry->created_at?->format('M Y') }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                        <span>{{ $entry->attachments_count }} file(s)</span>
                        <span>Created {{ $entry->created_at?->format('d M Y') }}</span>
                    </div>
                </a>
            @empty
                <p class="text-xs text-slate-400 px-1 py-6">
                    No months created yet for this folder.
                </p>
            @endforelse
        </div>

    </div>
</section>

@if (! $isConsultant)
    <div id="dh-folder-modal"
         class="font-plus fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-6">
            <h3 id="dh-folder-modal-title" class="text-sm font-semibold text-slate-900 mb-4">
                Add Month
            </h3>

            <form id="dh-folder-form" method="POST" action="{{ route('dh.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" id="dh-folder-method" value="POST">

                {{-- Folder is fixed: this folder --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Folder
                    </label>
                    <p class="text-xs font-semibold text-slate-900">
                        {{ $folderName }}
                    </p>
                    <input type="hidden" name="folder_name" value="{{ $folderName }}">
                </div>

                {{-- Month --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Month
                    </label>
                    <input
                        type="text"
                        name="month_label"
                        placeholder="e.g. Jan 2026"
                        class="w-[420px] max-w-full rounded-full border border-slate-300 px-3 py-2 text-xs focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                        required>
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
    $(function () {
        var $modal  = $('#dh-folder-modal');
        var $form   = $('#dh-folder-form');
        var $title  = $('#dh-folder-modal-title');
        var $method = $('#dh-folder-method');

        function openModal() {
            $modal.removeClass('hidden').addClass('flex');
        }

        function closeModal() {
            $modal.addClass('hidden').removeClass('flex');
        }

        // Open "Add Month" modal
        $('#dh-open-create-modal').on('click', function () {
            $title.text('Add Month');
            $form.attr('action', "{{ route('dh.store') }}");
            $method.val('POST');

            // reset only the month input; keep folder_name
            $form.find('input[name="month_label"]').val('');
            openModal();
        });

        $('#dh-folder-cancel').on('click', closeModal);

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        $modal.on('click', function (e) {
            if (e.target === this) closeModal();
        });
    });
</script>
@endpush
@endif
