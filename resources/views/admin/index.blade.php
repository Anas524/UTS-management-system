@extends('layouts.app')
@section('title','Admin')

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
                            Admin overview, {{ $user->name }} ✨
                        </h1>
                        <p class="mt-2 text-xs md:text-sm text-slate-300 max-w-xl">
                            Manage users and keep an eye on who has access. This space is only visible to
                            administrators.
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
                        <span>Go to Dashboard</span>
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
                   class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white px-3 py-1.5 font-medium shadow-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Overview
                </a>
                <a href="{{ route('admin.users') }}"
                   class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-700 hover:border-sky-400 hover:text-sky-600">
                    Users
                </a>
            </nav>
        </div>

        {{-- Stats row --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-sky-500/10"></div>
                <div class="relative space-y-2">
                    <p class="text-xs font-medium text-slate-500">Total Users</p>
                    <p class="font-plus text-3xl font-semibold text-slate-900">
                        {{ $totalUsers }}
                    </p>
                    <p class="text-[11px] text-slate-500">
                        All accounts that can sign in to UTS tools.
                    </p>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-emerald-500/10"></div>
                <div class="relative space-y-2">
                    <p class="text-xs font-medium text-slate-500">Admins</p>
                    <p class="font-plus text-3xl font-semibold text-slate-900">
                        {{ $adminsCount }}
                    </p>
                    <p class="text-[11px] text-slate-500">
                        Users with full access to the admin console.
                    </p>
                </div>
            </div>
        </div>

        {{-- Recent users --}}
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white/95 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="font-plus text-sm font-semibold text-slate-900">
                        Recent users
                    </h2>
                    <p class="text-[11px] text-slate-500">
                        Last accounts created in the system.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Name</th>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Email</th>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Joined</th>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Role</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($recentUsers as $u)
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
                            <td class="px-4 py-2 text-slate-600 whitespace-nowrap">
                                {{ $u->created_at->format('Y-m-d') }}
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-[11px] text-slate-500">
                                No users found yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
@endsection
