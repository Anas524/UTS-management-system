@extends('layouts.app')
@section('title','Admin • Users')

@section('content')
<div class="admin-wrap">
  <div class="admin-shell">

    <aside class="admin-side">
      <div class="admin-brand">
        <img src="{{ asset('images/UTS.png') }}" alt="UTS">
        <div>
          <strong>Admin</strong>
          <small>{{ auth()->user()->name }}</small>
        </div>
      </div>

      <nav class="admin-nav">
        <a class="admin-link" href="{{ route('admin.index') }}">Overview</a>
        <a class="admin-link admin-active" href="{{ route('admin.users') }}">Users</a>
      </nav>

      <div class="admin-quick">
        <a class="admin-btn admin-btn-primary" href="{{ route('dashboard') }}">Go to Dashboard</a>
        <a class="admin-btn admin-btn-outline" href="{{ route('home') }}">Go to Home</a>
      </div>
    </aside>

    <main class="admin-main">
      <h1 class="admin-title">Users</h1>

      <form class="admin-search" method="GET" action="{{ route('admin.users') }}">
        <input type="text" name="q" value="{{ $q }}"
          placeholder="Search name or email…">
        <button class="admin-btn admin-btn-primary" type="submit">Search</button>
      </form>

      @if ($errors->any())
      <div class="admin-alert error">{{ $errors->first() }}</div>
      @endif
      @if (session('status'))
      <div class="admin-alert success">{{ session('status') }}</div>
      @endif

      <div class="admin-card">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th class="right">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $u)
              <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                  @php
                  $role = $u->role ?? ($u->is_admin ? 'admin' : 'user');
                  @endphp
                  <span class="role-badge role-{{ $role }}">{{ ucfirst($role) }}</span>
                </td>
                <td class="right">
                  @if(auth()->id() !== $u->id)
                  <form method="POST" action="{{ route('admin.users.role', $u) }}" class="table-actions">
                    @csrf @method('PATCH')

                    @php $role = $u->role ?? ($u->is_admin ? 'admin' : 'user'); @endphp
                    <input type="hidden" name="role" value="{{ $role }}">

                    <div class="role-picker" data-role-picker>
                      <button type="button" class="rp-btn" aria-haspopup="listbox" aria-expanded="false">
                        <span class="rp-label">{{ ucfirst($role) }}</span>
                        <span class="rp-caret"></span>
                      </button>
                      <div class="rp-menu" role="listbox">
                        @foreach (['user'=>'User','consultant'=>'Consultant','admin'=>'Admin'] as $val=>$label)
                        <div class="rp-item" role="option"
                          data-value="{{ $val }}"
                          aria-selected="{{ $role === $val ? 'true' : 'false' }}">
                          <span>{{ $label }}</span>
                          <span class="rp-check">✓</span>
                        </div>
                        @endforeach
                      </div>
                    </div>

                    <button class="table-btn primary" type="submit">Update</button>
                  </form>
                  @else
                  <span class="muted">You</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="empty">No users found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- pagination --}}
        <div class="admin-paginate">
          {{ $users->onEachSide(1)->links() }}
        </div>
      </div>

    </main>

  </div>
</div>

@push('scripts')
<script>
$(function () {
  // Move menu to body on first open, and position it relative to the button using fixed coords
  function openPicker($picker){
    const $btn  = $picker.find('.rp-btn');
    let   $menu = $picker.data('rpMenu');
    if(!$menu){
      $menu = $picker.find('.rp-menu').first().appendTo('body'); // portal
      $picker.data('rpMenu', $menu);
    }

    // close all others first
    $('[data-role-picker]').each(function(){
      if(this !== $picker[0]) closePicker($(this));
    });

    // compute viewport position
    const rect = $btn[0].getBoundingClientRect();
    const mw   = Math.max(180, $menu.outerWidth());
    const gap  = 6;

    // place aligned to right edge of button; clamp to viewport
    const vw = $(window).width(), vh = $(window).height(), scrollY = window.scrollY, scrollX = window.scrollX;
    let left = rect.right - mw + scrollX;
    let top  = rect.bottom + gap + scrollY;

    left = Math.max(8 + scrollX, Math.min(left, vw - mw - 8 + scrollX));
    // if not enough space below, flip above
    const neededH = $menu.outerHeight() + gap;
    if (rect.bottom + neededH > vh) top = rect.top - neededH + scrollY;

    $menu.css({ left: left + 'px', top: top + 'px', minWidth: mw + 'px' }).addClass('open');
    $btn.attr('aria-expanded','true');
  }

  function closePicker($picker){
    const $menu = $picker.data('rpMenu') || $picker.find('.rp-menu');
    $menu.removeClass('open');
    $picker.find('.rp-btn').attr('aria-expanded','false');
  }

  // Toggle
  $(document).on('click', '[data-role-picker] .rp-btn', function(e){
    e.preventDefault();
    const $picker = $(this).closest('[data-role-picker]');
    const $menu = $picker.data('rpMenu') || $picker.find('.rp-menu');
    if($menu.hasClass('open')) closePicker($picker); else openPicker($picker);
  });

  // Choose
  $(document).on('click', '.rp-menu .rp-item', function(){
    const $menu   = $(this).closest('.rp-menu');
    const $picker = $('[data-role-picker]').filter(function(){ return $(this).data('rpMenu')?.[0] === $menu[0]; }).first();
    const $item   = $(this);
    const value   = $item.data('value');

    $menu.find('.rp-item').attr('aria-selected','false');
    $item.attr('aria-selected','true');

    $picker.find('.rp-label').text($.trim($item.text()));
    $picker.closest('form').find('input[name="role"]').val(value);

    closePicker($picker);
  });

  // Close on outside click / scroll / resize / Esc
  $(document).on('click', function(e){
    if ($(e.target).closest('[data-role-picker], .rp-menu').length === 0) {
      $('[data-role-picker]').each(function(){ closePicker($(this)); });
    }
  });
  $(window).on('scroll resize', function(){
    $('[data-role-picker]').each(function(){ closePicker($(this)); });
  });
  $(document).on('keydown', function(e){
    if(e.key === 'Escape'){
      $('[data-role-picker]').each(function(){ closePicker($(this)); });
    }
  });
});
</script>
@endpush
@endsection