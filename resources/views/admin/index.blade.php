@extends('layouts.app')
@section('title','Admin')

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
        <a class="admin-link admin-active" href="{{ route('admin.index') }}">Overview</a>
        <a class="admin-link" href="{{ route('admin.users') }}">Users</a>
      </nav>

      <div class="admin-quick">
        <a class="admin-btn admin-btn-primary" href="{{ route('dashboard') }}">Go to Dashboard</a>
        <a class="admin-btn admin-btn-outline" href="{{ route('home') }}">Go to Home</a>
      </div>
    </aside>

    <main class="admin-main">
      <h1 class="admin-title">Overview</h1>

      <div class="admin-stats">
        <div class="admin-card admin-stat">
          <div class="stat-label">Total Users</div>
          <div class="stat-value">{{ $totalUsers }}</div>
        </div>
        <div class="admin-card admin-stat">
          <div class="stat-label">Admins</div>
          <div class="stat-value">{{ $adminsCount }}</div>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head">
          <h3>Recent Users</h3>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Name</th><th>Email</th><th>Joined</th><th>Role</th></tr>
            </thead>
            <tbody>
            @forelse($recentUsers as $u)
              <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->created_at->format('Y-m-d') }}</td>
                <td>{{ $u->is_admin ? 'Admin' : 'User' }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="empty">No data</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </main>

  </div>
</div>
@endsection
