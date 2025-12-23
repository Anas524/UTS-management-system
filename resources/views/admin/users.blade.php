@extends('layouts.app')
@section('title','Admin • Users')

@push('head-scripts')
<style>
  /* Ensure our custom role button never shows any extra caret from global CSS */
  .rp-btn::after {
    content: none !important;
  }
</style>
@endpush

@section('content')
@php
/** @var \App\Models\User $user */
$user = auth()->user();
$role = $user->role ?? ($user->is_admin ? 'admin' : 'user');
$roleLabel = $user->is_admin ? 'Admin' : ($role === 'consultant' ? 'Consultant' : 'User');
@endphp

<section class="py-10 px-4">
  <div class="text-dec-none font-plus max-w-6xl mx-auto space-y-8">

    {{-- Top hero --}}
    <div
      class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-sky-900 border border-slate-800 shadow-xl">
      <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-sky-500/20 blur-3xl pointer-events-none"></div>

      <div class="relative flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between">
        <div class="flex items-start gap-4">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/10 border border-sky-500/40">
            <img src="{{ asset('images/UTS.png') }}" alt="UTS"
              class="h-8 w-8 object-contain">
          </div>

          <div>
            <p class="font-plus text-xs uppercase tracking-[0.18em] text-slate-400">
              Admin Console
            </p>
            <h1 class="font-plus text-2xl md:text-3xl font-semibold tracking-tight text-white">
              User management, {{ $user->name }} ✨
            </h1>
            <p class="mt-2 text-xs md:text-sm text-slate-300 max-w-xl">
              Search, review, and adjust roles for everyone who can access UTS tools.
            </p>

            <div class="mt-3 inline-flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1 rounded-full border border-emerald-400/40 bg-emerald-500/10 px-3 py-1 text-[11px] font-medium text-emerald-200">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                {{ $roleLabel }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-stretch gap-2 md:items-end text-xs">
          <a href="{{ route('dashboard') }}"
            class="inline-flex items-center justify-center rounded-full bg-white text-slate-900 px-4 py-2 font-semibold shadow-md hover:bg-slate-100">
            Go to Dashboard
          </a>
          <a href="{{ route('home') }}"
            class="inline-flex items-center justify-center rounded-full border border-slate-500/60 bg-slate-900/40 px-4 py-2 font-semibold text-slate-100 hover:border-sky-500 hover:text-sky-200 mt-1">
            Go to Home
          </a>
        </div>
      </div>
    </div>

    {{-- Admin navigation --}}
    <div
      class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm">
      <div class="flex items-center gap-2 text-xs">
        <span class="font-plus text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
          Admin navigation
        </span>
      </div>

      <nav class="flex items-center gap-2 text-xs">
        <a href="{{ route('admin.index') }}"
          class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-700 hover:border-sky-400 hover:text-sky-600">
          Overview
        </a>
        <a href="{{ route('admin.users') }}"
          class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white px-3 py-1.5 font-medium shadow-sm">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
          Users
        </a>
      </nav>
    </div>

    {{-- Search + alerts --}}
    <div class="space-y-3">
      {{-- Search bar --}}
      <form
        class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-sm md:flex-row md:items-center md:justify-between"
        method="GET"
        action="{{ route('admin.users') }}">

        <div class="flex-1">
          <p class="font-plus text-xs font-semibold text-slate-700">
            Search users
          </p>
          <p class="text-[11px] text-slate-500">
            Filter by name or email to quickly find a specific account.
          </p>
        </div>

        {{-- RIGHT SIDE: input + button --}}
        <div class="flex flex-col w-full gap-3 md:w-auto md:flex-row md:items-center md:justify-end">
          {{-- INPUT --}}
          <div class="relative md:mr-3">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 15.75L19.5 19.5M10.5 6.75A3.75 3.75 0 1110.5 14.25A3.75 3.75 0 0110.5 6.75Z" />
              </svg>
            </span>
            <input
              type="text"
              name="q"
              value="{{ $q }}"
              class="rounded-full border border-slate-200 bg-slate-50 pl-8 pr-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-400"
              placeholder="Search name or email…">
          </div>

          {{-- BUTTON --}}
          <button
            class="inline-flex items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800
               w-full md:w-auto"
            type="submit">
            Search
          </button>
        </div>
      </form>

      {{-- Alerts --}}
      @if ($errors->any())
      <div
        class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-2 text-[11px] text-rose-700 shadow-sm">
        {{ $errors->first() }}
      </div>
      @endif

      @if (session('status'))
      <div
        class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-[11px] text-emerald-700 shadow-sm">
        {{ session('status') }}
      </div>
      @endif
    </div>

    {{-- Users table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white/95 shadow-sm">
      <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <div>
          <h2 class="font-plus text-sm font-semibold text-slate-900">
            Users
          </h2>
          <p class="text-[11px] text-slate-500">
            Manage roles and permissions for each account.
          </p>
        </div>

        <span class="text-[11px] text-slate-500">
          {{ $users->total() }} total
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
              <th class="px-4 py-2 text-left font-semibold text-slate-500">Name</th>
              <th class="px-4 py-2 text-left font-semibold text-slate-500">Email</th>
              <th class="px-4 py-2 text-left font-semibold text-slate-500">Role</th>
              <th class="px-4 py-2 text-right font-semibold text-slate-500">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($users as $u)
            @php
            $uRole = $u->role ?? ($u->is_admin ? 'admin' : 'user');
            $uRoleLabel = $u->is_admin ? 'Admin' : ($uRole === 'consultant' ? 'Consultant' : 'User');
            @endphp
            <tr class="hover:bg-slate-50/80 transition-colors">
              <td class="px-4 py-2 text-slate-900">
                {{ $u->name }}
              </td>
              <td class="px-4 py-2 text-slate-600">
                {{ $u->email }}
              </td>
              <td class="px-4 py-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium
                                    @if($u->is_admin)
                                        bg-slate-900 text-white
                                    @elseif($uRole === 'consultant')
                                        bg-sky-50 text-sky-700 border border-sky-100
                                    @else
                                        bg-slate-50 text-slate-700 border border-slate-100
                                    @endif">
                  {{ $uRoleLabel }}
                </span>
              </td>
              <td class="px-4 py-2 text-right">
                @if(auth()->id() !== $u->id)
                <form method="POST"
                  action="{{ route('admin.users.role', $u) }}"
                  class="inline-flex items-center gap-2">
                  @csrf
                  @method('PATCH')

                  @php $roleVal = $uRole; @endphp
                  <input type="hidden" name="role" value="{{ $roleVal }}">

                  {{-- Role picker (keep data-role-picker + base structure) --}}
                  <div class="role-picker relative" data-role-picker>
                    <button type="button"
                      class="rp-btn inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:border-sky-400 hover:text-sky-700"
                      aria-haspopup="listbox"
                      aria-expanded="false">
                      <span class="rp-label">{{ ucfirst($roleVal) }}</span>
                      {{-- Single caret icon (no double arrows) --}}
                      <span class="rp-caret block text-[9px] text-slate-400 leading-none">▾</span>
                    </button>

                    {{-- Menu body (JS will portal this to <body> & position below / above) --}}
                    <div class="rp-menu hidden z-40 rounded-xl border border-slate-200 bg-white py-1 text-[11px] shadow-lg"
                      role="listbox">
                      @foreach (['user'=>'User','consultant'=>'Consultant','admin'=>'Admin'] as $val=>$label)
                      <div class="rp-item flex cursor-pointer items-center justify-between px-3 py-1.5 text-slate-700 hover:bg-slate-50"
                        role="option"
                        data-value="{{ $val }}"
                        aria-selected="{{ $roleVal === $val ? 'true' : 'false' }}">
                        <span>{{ $label }}</span>
                        <span class="rp-check text-[10px] text-sky-500 {{ $roleVal === $val ? '' : 'opacity-0' }}">✓</span>
                      </div>
                      @endforeach
                    </div>
                  </div>

                  <button
                    class="table-btn primary inline-flex items-center rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-800"
                    type="submit">
                    Update
                  </button>
                </form>
                @else
                <span class="text-[11px] text-slate-400">You</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" class="px-4 py-6 text-center text-[11px] text-slate-500">
                No users found.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="border-t border-slate-100 px-4 py-3">
        <div class="flex justify-between items-center text-[11px] text-slate-500">
          <span>
            Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}
          </span>
          <div class="text-xs">
            {{ $users->onEachSide(1)->links() }}
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

@push('scripts')
<script>
  $(function() {
    // --- role picker behaviour (same logic, just UI cleaned) ---

    function openPicker($picker) {
      const $btn = $picker.find('.rp-btn');
      let $menu = $picker.data('rpMenu');
      if (!$menu) {
        $menu = $picker.find('.rp-menu').first().appendTo('body'); // portal
        $picker.data('rpMenu', $menu);
      }

      // close all others first
      $('[data-role-picker]').each(function() {
        if (this !== $picker[0]) closePicker($(this));
      });

      const rect = $btn[0].getBoundingClientRect();
      const gap = 6;

      // measure AFTER we have it in body
      const mw = Math.max(180, $menu.outerWidth());
      const mh = $menu.outerHeight();

      const vw = $(window).width();
      const vh = $(window).height();

      // start aligned to the left of the button
      let left = rect.left;
      let top = rect.bottom + gap;

      // clamp horizontally so it never goes off-screen
      left = Math.max(8, Math.min(left, vw - mw - 8));

      // if there's not enough room below, flip above
      const neededH = mh + gap;
      if (rect.bottom + neededH > vh) {
        top = rect.top - mh - gap;
      }

      $menu
        .css({
          left: left + 'px',
          top: top + 'px',
          minWidth: mw + 'px',
          position: 'fixed'
        })
        .addClass('open')
        .removeClass('hidden');

      $btn.attr('aria-expanded', 'true');
    }

    function closePicker($picker) {
      const $menu = $picker.data('rpMenu') || $picker.find('.rp-menu');
      $menu.removeClass('open').addClass('hidden');
      $picker.find('.rp-btn').attr('aria-expanded', 'false');
    }

    // Toggle
    $(document).on('click', '[data-role-picker] .rp-btn', function(e) {
      e.preventDefault();
      const $picker = $(this).closest('[data-role-picker]');
      const $menu = $picker.data('rpMenu') || $picker.find('.rp-menu');
      if ($menu.hasClass('open')) closePicker($picker);
      else openPicker($picker);
    });

    // Choose
    $(document).on('click', '.rp-menu .rp-item', function() {
      const $menu = $(this).closest('.rp-menu');
      const $picker = $('[data-role-picker]').filter(function() {
        return $(this).data('rpMenu')?.[0] === $menu[0];
      }).first();
      const $item = $(this);
      const value = $item.data('value');

      $menu.find('.rp-item').attr('aria-selected', 'false');
      $menu.find('.rp-check').addClass('opacity-0');
      $item.attr('aria-selected', 'true');
      $item.find('.rp-check').removeClass('opacity-0');

      $picker.find('.rp-label').text($.trim($item.text()));
      $picker.closest('form').find('input[name="role"]').val(value);

      closePicker($picker);
    });

    // Close on outside click / scroll / resize / Esc
    $(document).on('click', function(e) {
      if ($(e.target).closest('[data-role-picker], .rp-menu').length === 0) {
        $('[data-role-picker]').each(function() {
          closePicker($(this));
        });
      }
    });
    $(window).on('scroll resize', function() {
      $('[data-role-picker]').each(function() {
        closePicker($(this));
      });
    });
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape') {
        $('[data-role-picker]').each(function() {
          closePicker($(this));
        });
      }
    });
  });
</script>
@endpush
@endsection