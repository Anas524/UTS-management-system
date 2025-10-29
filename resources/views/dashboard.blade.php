@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="dash-wrap">
    <div class="dash-card">
        <div class="dash-head">
            <img class="dash-logo" src="{{ asset('images/UTS.png') }}" alt="UTS">
            <div>
                <h1 class="dash-title">Hello, {{ auth()->user()->name }} 👋</h1>
                @php
                $role = auth()->user()->role ?? (auth()->user()->is_admin ? 'admin' : 'user');
                @endphp
                <p class="dash-sub">Logged in as
                    <span class="dash-badge">{{ $role === 'admin' || auth()->user()->is_admin ? 'Admin' : ($role === 'consultant' ? 'Consultant' : 'User') }}</span>
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
                    <dd>{{ $role === 'admin' || auth()->user()->is_admin ? 'Admin' : ($role === 'consultant' ? 'Consultant' : 'User') }}</dd>
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
            <p class="dash-help">
                @if($role === 'consultant')
                You have read-only access. You can open sheets, view/download attachments, and export to Excel.
                @else
                Create and manage monthly expense sheets.
                @endif
            </p>
            <div class="dash-actions">
                <a href="{{ route('expenses.index') }}" class="dash-btn dash-btn-primary">Open Expense Sheets</a>

                @can('create', App\Models\ExpenseSheet::class)
                <a href="{{ route('expenses.index', ['new' => 1]) }}" class="dash-btn dash-btn-gold">Create New Sheet</a>
                @endcan
            </div>
        </div>

        {{-- Purchase Orders --}}
        <div class="dash-box">
            <h3>Purchase Orders</h3>
            <p class="dash-help">
                @if($role === 'consultant')
                You have read-only access. You can open POs and download files.
                @else
                Create, edit, and manage purchase orders.
                @endif
            </p>

            <div class="dash-actions">
                <a href="{{ route('po.index') }}" class="dash-btn dash-btn-primary">Open Purchase Orders</a>

                @can('create', App\Models\PurchaseOrder::class)
                <a href="{{ route('po.create') }}" class="dash-btn dash-btn-gold">Create New PO</a>
                @endcan
            </div>
        </div>

    </div>
</div>
@endsection