@extends('layouts.app')
@section('title', 'Inventory')

@section('content')
<section class="py-10 px-4" data-sl-auto-open="{{ request('new') ? '1' : '0' }}">
    <div class="max-w-6xl mx-auto font-plus text-dec-none">

        @php
        /** @var \Illuminate\Support\Collection $inventories */
        $inventories = $inventories ?? collect();
        $totalInventories = $inventories->count();
        $lastInventory = $inventories->max('created_at');
        @endphp

        {{-- Back + actions --}}
        <div class="flex items-center justify-between gap-4 mb-8">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:border-sky-300 hover:text-sky-700">
                ← Back to dashboard
            </a>

            {{-- New Inventory (opens modal) – only for non-consultants --}}
            @if(($role ?? null) !== 'consultant')
            <button
                type="button"
                id="sl-open-create"
                class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-slate-800">
                + New Inventory
            </button>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Title + summary --}}
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">
                    Inventory
                </h1>
                <p class="mt-2 text-xs md:text-sm text-slate-500 max-w-xl">
                    @if(($role ?? null) === 'consultant')
                    View inventory lists and open each ledger to see stock movements in read-only mode.
                    @else
                    Create separate inventory lists and open each ledger to manage stock movements.
                    @endif
                </p>

                {{-- Small stats line --}}
                <div class="mt-5 flex flex-wrap gap-3 text-[11px] uppercase tracking-[0.16em] text-slate-500">
                    <div class="rounded-full bg-white/80 px-3 py-2 shadow-sm">
                        <span class="opacity-70">Total inventories</span>
                        <span class="ml-2 text-slate-900 font-semibold normal-case">
                            {{ number_format($totalInventories, 0, '.', ',') }}
                        </span>
                    </div>

                    @if($lastInventory)
                    <div class="rounded-full bg-white/80 px-3 py-2 shadow-sm">
                        <span class="opacity-70">Last created</span>
                        <span class="ml-2 text-slate-900 font-semibold normal-case">
                            {{ \Illuminate\Support\Carbon::parse($lastInventory)->format('d M Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Card list (each = one inventory) --}}
            <div class="rounded-[2rem] bg-white/90 shadow-[0_22px_60px_rgba(15,23,42,0.12)] border border-slate-100">
                @forelse($inventories as $inv)
                <div class="border-b border-slate-100 last:border-b-0 px-5 py-4 md:px-7 md:py-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        {{-- Avatar circle with first letter of inventory name --}}
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white shadow-sm">
                            {{ strtoupper(mb_substr($inv->name ?? 'X', 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">
                                {{ $inv->name ?? 'Untitled inventory' }}
                            </p>
                            <p class="text-[11px] text-slate-500 truncate">
                                {{ $inv->description ?: 'No description added yet.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap justify-end text-[11px]">
                        {{-- Created date --}}
                        <div class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-3 py-1 border border-slate-100 text-slate-600">
                            <span class="opacity-70">Created</span>
                            <span class="font-medium text-slate-900">
                                {{ $inv->created_at ? $inv->created_at->format('d M Y') : '—' }}
                            </span>
                        </div>

                        {{-- Open ledger --}}
                        <a href="{{ route('sl.show', $inv->id) }}"
                            class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-slate-800">
                            Open Ledger
                            <span class="text-[10px]">›</span>
                        </a>

                        {{-- Delete inventory (only for non-consultants) --}}
                        @if(($role ?? null) !== 'consultant')
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-600 shadow-sm hover:bg-rose-100 sl-delete-btn"
                            data-id="{{ $inv->id }}"
                            data-name="{{ $inv->name ?? 'this inventory' }}"
                            data-url="{{ route('sl.inventories.destroy', $inv->id) }}">
                            Delete
                            <span class="text-[10px]">✕</span>
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 md:px-7 text-[11px] text-slate-500">
                    @if(($role ?? null) === 'consultant')
                    No inventories are available yet. Please contact your admin if you need access.
                    @else
                    No inventories yet. Click <span class="font-semibold">“New Inventory”</span> to create your first list.
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Create Inventory Modal --}}
    @if(($role ?? null) !== 'consultant')
    <div
        id="sl-create-modal"
        class="font-plus fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl border border-slate-200 px-8 py-6">
            {{-- Header + close --}}
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        New Inventory
                    </h2>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Give this inventory a clear name and optional description.
                    </p>
                </div>

                {{-- rounded close button (same feel as Doc Hub) --}}
                <button
                    type="button"
                    data-sl-close
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-[11px] text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                    <span class="sr-only">Close</span>
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('sl.inventories.store') }}" class="space-y-4">
                @csrf

                {{-- Inventory name --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Folder name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        required
                        class="w-[485px] rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-900 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400">
                </div>

                {{-- Description (like “Month” field, just taller) --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Description
                    </label>
                    <textarea
                        name="description"
                        rows="3"
                        class="w-[485px] rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-900 resize-y min-h-[2.75rem] focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"></textarea>
                </div>

                {{-- Buttons aligned like Doc Hub --}}
                <div class="mt-5 flex items-center justify-end gap-2 text-[11px]">
                    <button
                        type="button"
                        class="rounded-full px-4 py-1.5 border border-slate-300 text-slate-600 bg-white hover:bg-slate-50"
                        data-sl-close>
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-full px-5 py-1.5 bg-slate-900 text-white font-semibold shadow-sm hover:bg-slate-800">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Inventory Modal --}}
    @if(($role ?? null) !== 'consultant')
    <div
        id="sl-delete-modal"
        class="font-plus fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 px-6 py-5">
            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Delete inventory
                    </h2>
                    <p class="mt-1 text-[11px] text-slate-500">
                        This will permanently delete the inventory folder
                        <span class="font-semibold text-slate-900" id="sl-delete-name">—</span>
                        and all rows inside its ledger. This action cannot be undone.
                    </p>
                </div>

                <button
                    type="button"
                    data-sl-delete-close
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-[11px] text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                    ✕
                </button>
            </div>

            <form id="sl-delete-form" method="POST" action="#">
                @csrf
                @method('DELETE')

                <div class="mt-3 flex items-center justify-end gap-2 text-[11px]">
                    <button
                        type="button"
                        class="rounded-full px-4 py-1.5 border border-slate-300 text-slate-600 bg-white hover:bg-slate-50"
                        data-sl-delete-close>
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-full px-5 py-1.5 bg-rose-600 text-white font-semibold shadow-sm hover:bg-rose-700">
                        Yes, delete folder
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</section>
@endsection

@push('scripts')
<script>
    $(function() {
        // ----- CREATE MODAL -----
        var $openBtn = $('#sl-open-create');
        var $createModal = $('#sl-create-modal');

        function openCreateModal() {
            $createModal.removeClass('hidden').addClass('flex');
        }
        function closeCreateModal() {
            $createModal.removeClass('flex').addClass('hidden');
        }

        if ($openBtn.length && $createModal.length) {
            // Open by button
            $openBtn.on('click', function(e) {
                e.preventDefault();
                openCreateModal();
            });

            // Close buttons inside modal
            $createModal.find('[data-sl-close]').on('click', function(e) {
                e.preventDefault();
                closeCreateModal();
            });

            // Backdrop click
            $createModal.on('click', function(e) {
                if (e.target === this) {
                    closeCreateModal();
                }
            });
        }

        // Auto-open when coming from dashboard with ?new=1
        var $autoWrap = $('[data-sl-auto-open]');
        if ($autoWrap.length && String($autoWrap.data('sl-auto-open')) === '1') {
            openCreateModal();
        }

        // ----- DELETE MODAL -----
        var $deleteModal = $('#sl-delete-modal');
        var $deleteForm  = $('#sl-delete-form');
        var $deleteName  = $('#sl-delete-name');

        if ($deleteModal.length) {
            // Open delete modal when clicking delete button
            $(document).on('click', '.sl-delete-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);

                var invName = $btn.data('name') || 'this inventory';
                var url     = $btn.data('url');

                $deleteName.text(invName);
                $deleteForm.attr('action', url);

                $deleteModal.removeClass('hidden').addClass('flex');
            });

            function closeDeleteModal() {
                $deleteModal.removeClass('flex').addClass('hidden');
            }

            // Close via buttons
            $deleteModal.find('[data-sl-delete-close]').on('click', function (e) {
                e.preventDefault();
                closeDeleteModal();
            });

            // Backdrop click
            $deleteModal.on('click', function (e) {
                if (e.target === this) {
                    closeDeleteModal();
                }
            });
        }
    });
</script>
@endpush
