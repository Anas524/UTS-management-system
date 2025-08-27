@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="dash-wrap">
    <div class="dash-card">
        <div class="dash-head">
            <img class="dash-logo" src="{{ asset('images/UTS.png') }}" alt="UTS">
            <div>
                <h1 class="dash-title">Hello, {{ auth()->user()->name }} 👋</h1>
                <p class="dash-sub">Logged in as
                    <span class="dash-badge">{{ auth()->user()->is_admin ? 'Admin' : 'User' }}</span>
                </p>
            </div>
        </div>

        <div class="dash-grid">
            <div class="dash-box">
                <h3>Your details</h3>
                <dl class="dash-dl">
                    <dt>Email</dt>
                    <dd>{{ auth()->user()->email }}</dd>
                    <dt>Role</dt>
                    <dd>{{ auth()->user()->is_admin ? 'Admin' : 'User' }}</dd>
                </dl>
            </div>

            <div class="dash-box">
                <h3>Quick actions</h3>
                <div class="dash-actions">
                    <a href="{{ route('home') }}" class="dash-btn dash-btn-primary">Go to Home</a>
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.index') }}" class="dash-btn dash-btn-gold">Open Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dash-btn dash-btn-outline" type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="dash-box">
            <h3>Expense Sheets</h3>
            <p class="dash-help">Create and manage monthly expense sheets.</p>
            <div class="dash-actions">
                <a href="{{ route('expenses.index') }}" class="dash-btn dash-btn-primary">Open Expense Sheets</a>
                <a href="{{ route('expenses.index', ['new' => 1]) }}" class="dash-btn dash-btn-gold">Create New Sheet</a>
            </div>
        </div>

    </div>
</div>
@endsection