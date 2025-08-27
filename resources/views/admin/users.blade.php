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
              <tr><th>Name</th><th>Email</th><th>Role</th><th class="right">Action</th></tr>
            </thead>
            <tbody>
            @forelse($users as $u)
              <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                  <span class="role-badge {{ $u->is_admin ? 'role-admin' : 'role-user' }}">
                    {{ $u->is_admin ? 'Admin' : 'User' }}
                  </span>
                </td>
                <td class="right">
                  @if(auth()->id() !== $u->id)
                  <form method="POST" action="{{ route('admin.users.role', $u) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="is_admin" value="{{ $u->is_admin ? 0 : 1 }}">
                    <button class="table-btn {{ $u->is_admin ? 'danger' : 'primary' }}" type="submit">
                      {{ $u->is_admin ? 'Demote to User' : 'Promote to Admin' }}
                    </button>
                  </form>
                  @else
                    <span class="muted">You</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="empty">No users found</td></tr>
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
@endsection
