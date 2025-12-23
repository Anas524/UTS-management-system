@extends('layouts.app')
@section('title','Dashboard')

@section('content')
@php
$user = auth()->user();
$role = $user->role ?? ($user->is_admin ? 'admin' : 'user');
$roleLabel = $user->is_admin || $role === 'admin'
? 'Admin'
: ($role === 'consultant' ? 'Consultant' : 'User');
@endphp

<section class="px-4 py-10 bg-gradient-to-b from-slate-50 via-slate-100 to-slate-50">
    <div class="text-dec-none mx-auto max-w-6xl space-y-8">

        {{-- HERO -------------------------------------------------------------- --}}
        <div class="relative overflow-hidden rounded-3xl dashboard-hero text-white shadow-2xl">

            {{-- subtle glow circles --}}
            <div class="pointer-events-none absolute -left-16 -top-16 h-40 w-40 rounded-full bg-sky-500/25 blur-2xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-0 h-52 w-52 rounded-full bg-utsGold/25 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 px-6 py-8 md:flex-row md:items-center md:justify-between md:px-10">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
                        <img src="{{ asset('images/UTS.png') }}" alt="UTS" class="h-10 w-10 object-contain">
                    </div>
                    <div>
                        <h1 class="font-plus text-2xl md:text-3xl font-semibold tracking-tight">
                            Welcome back, {{ $user->name }} ✨
                        </h1>
                        <p class="mt-2 font-plus text-xs text-slate-200/90">
                            @if($role === 'consultant')
                            View expense sheets, purchase orders, payslips and shared documents shared with you, all in one place.
                            @else
                            Manage your expense sheets, purchase orders, payslips and shared documents from a single place.
                            @endif
                        </p>

                        <div class="mt-4 flex flex-wrap gap-3 text-[11px] font-plus">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 border border-white/20">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                                Logged in as <strong class="font-semibold">{{ $roleLabel }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-black/20 px-3 py-1 border border-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Quick access to all finance tools
                            </span>
                        </div>
                    </div>
                </div>

                {{-- hero actions --}}
                <div class="flex flex-col gap-3 text-xs font-plus md:items-end">
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-white text-slate-900 px-5 py-2 font-semibold shadow hover:bg-slate-100">
                        Go to main site
                    </a>

                    @if($user->is_admin)
                    <a href="{{ route('admin.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-utsGold text-slate-900 px-5 py-2 font-semibold shadow hover:bg-yellow-400">
                        Open Admin Panel
                    </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/40 bg-white/5 px-4 py-1.5 font-semibold text-slate-50 hover:bg-white/10">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- GRID OF MODULE CARDS ---------------------------------------------- --}}
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

            {{-- Expense Sheets --}}
            <div
                class="group rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm hover:shadow-lg hover:border-sky-300 transition-all duration-200">
                <div class="flex items-start gap-3 px-5 pt-5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 group-hover:bg-sky-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h3M6 5.25h12M5.25 3.75h13.5A1.5 1.5 0 0120.25 5.25v13.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-plus text-sm font-semibold text-slate-900">Expense Sheets</h3>
                        <p class="font-plus text-xs text-slate-500 mt-1">
                            @if($role === 'consultant')
                            View monthly sheets, attachments and export to Excel in read-only mode.
                            @else
                            Create, manage and export monthly expense sheets for the team.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-4 flex flex-wrap gap-2 text-xs font-plus">
                    <a href="{{ route('expenses.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-1.5 font-semibold text-white hover:bg-sky-600">
                        Open Expense Sheets
                    </a>

                    @can('create', App\Models\ExpenseSheet::class)
                    <a href="{{ route('expenses.index', ['new' => 1]) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-1.5 font-semibold text-white hover:bg-slate-800">
                        New Sheet
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Purchase Orders --}}
            <div
                class="group rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm hover:shadow-lg hover:border-amber-300 transition-all duration-200">
                <div class="flex items-start gap-3 px-5 pt-5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M4.5 7.5h15M7.5 12h9M7.5 16.5h6M4.5 21h15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-plus text-sm font-semibold text-slate-900">Purchase Orders</h3>
                        <p class="font-plus text-xs text-slate-500 mt-1">
                            @if($role === 'consultant')
                            Open POs, check line items and download attached files.
                            @else
                            Create, edit and export professional purchase orders.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-4 flex flex-wrap gap-2 text-xs font-plus">
                    <a href="{{ route('po.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-1.5 font-semibold text-white hover:bg-sky-600">
                        Open POs
                    </a>

                    @can('create', App\Models\PurchaseOrder::class)
                    <a href="{{ route('po.create') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-utsGold px-4 py-1.5 font-semibold text-slate-900 hover:bg-yellow-400">
                        New PO
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Payslips --}}
            <div
                class="group rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm hover:shadow-lg hover:border-rose-300 transition-all duration-200">
                <div class="flex items-start gap-3 px-5 pt-5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 group-hover:bg-rose-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8.25v7.5m0 0c-1.5 0-2.625-.75-3.375-1.875M12 15.75c1.5 0 2.625-.75 3.375-1.875M4.5 4.5h15v15h-15z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-plus text-sm font-semibold text-slate-900">Payslips</h3>
                        <p class="font-plus text-xs text-slate-500 mt-1">
                            @if($role === 'consultant')
                            Review payslips and salary breakdown in a clean read-only view.
                            @else
                            Generate payslips with automatic BPJS & tax calculation.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-4 flex flex-wrap gap-2 text-xs font-plus">
                    <a href="{{ route('payslips.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-1.5 font-semibold text-white hover:bg-sky-600">
                        Open Payslips
                    </a>

                    @if($role === 'admin' || $user->is_admin)
                    <a href="{{ route('payslips.index', ['new' => 1]) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-1.5 font-semibold text-white hover:bg-slate-800">
                        New Payslip
                    </a>
                    @endif
                </div>
            </div>

            {{-- Stock Ledger --}}
            <div
                class="group rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm hover:shadow-lg hover:border-emerald-300 transition-all duration-200">
                <div class="flex items-start gap-3 px-5 pt-5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100">
                        {{-- box / inventory icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 7.5L12 3l8.25 4.5M4.5 8.25v8.25L12 21l7.5-4.5V8.25" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 12l7.5-4.5M12 12L4.5 7.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-plus text-sm font-semibold text-slate-900">Stock Ledger</h3>
                        <p class="font-plus text-xs text-slate-500 mt-1">
                            @if($role === 'consultant')
                            Monitor current stock levels and view in / out movements in read-only mode.
                            @else
                            Track inventory in & out with automatic current stock and restock indicators.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-4 flex flex-wrap gap-2 text-xs font-plus">
                    <a href="{{ route('sl.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-1.5 font-semibold text-white hover:bg-sky-600">
                        Open Inventory
                    </a>

                    @if($role === 'admin' || $user->is_admin)
                    <a href="{{ route('sl.index', ['new' => 1]) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-1.5 font-semibold text-white hover:bg-slate-800">
                        New Inventory
                    </a>
                    @endif
                </div>
            </div>

            {{-- Document Hub (wider card) --}}
            <div
                class="group lg:col-span-2 rounded-2xl bg-slate-900 text-slate-50 border border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-200">
                <div class="flex flex-col gap-4 px-5 pt-5 md:flex-row md:items-center md:justify-between md:px-7">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/20 text-sky-300 group-hover:bg-sky-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.5h10.5L20.25 10.5v9H3.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 13.5h6M9 16.5h3" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-plus text-sm font-semibold text-white">Document Hub</h3>
                            <p class="font-plus text-xs text-slate-300 mt-1 max-w-xl">
                                @if($role === 'consultant')
                                Central place to quickly view and download shared invoices, tax proofs and other documents.
                                @else
                                A centralized hub to upload, organize and share important documents with consultants and admins.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-plus">
                        <a href="{{ route('dh.index') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-1.5 font-semibold text-white hover:bg-sky-600">
                            Open Document Hub
                        </a>

                        @if($role === 'admin' || $user->is_admin)
                        <a href="{{ route('dh.index', ['new' => 1]) }}"
                            class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 font-semibold text-slate-900 hover:bg-slate-100">
                            Add New Record
                        </a>
                        @endif
                    </div>
                </div>
                <div class="px-5 pb-5 pt-3 md:px-7">
                    <p class="font-plus text-[11px] text-slate-400">
                        Tip: Use the Document Hub to store invoices, payslip proofs and any files you want consultants to access
                        without opening Expense Sheets or Purchase Orders.
                    </p>
                </div>
            </div>

            {{-- Your account (summary) --}}
            <div
                class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-200">
                <div class="flex items-start gap-3 px-5 pt-5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.5 20.25a8.25 8.25 0 0115 0" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-plus text-sm font-semibold text-slate-900">Your account</h3>
                        <p class="font-plus text-xs text-slate-500 mt-1">
                            Quick summary of who you are logged in as.
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-5 pt-4">
                    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-2 text-xs font-plus text-slate-700">
                        <dt class="font-semibold text-slate-500">Name</dt>
                        <dd>{{ $user->name }}</dd>

                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="break-all">{{ $user->email }}</dd>

                        <dt class="font-semibold text-slate-500">Role</dt>
                        <dd>{{ $roleLabel }}</dd>
                    </dl>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection