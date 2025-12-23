@extends('layouts.app')
@section('title','Login')

@section('content')
<section
    class="h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-sky-50 to-slate-100
           flex items-center justify-center px-4 font-plus">

    <div class="relative w-full max-w-md text-dec-none">
        {{-- soft glow behind card --}}
        <div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-64 -translate-x-1/2
                    rounded-full bg-utsGold/25 blur-3xl"></div>

        {{-- card --}}
        <div
            class="relative rounded-3xl border border-white/70 bg-white/90
                   shadow-[0_24px_80px_rgba(15,23,42,0.20)] backdrop-blur-xl px-8 pt-8 pb-7">

            {{-- header --}}
            <div class="flex items-center gap-4 mb-6">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl
                           bg-white shadow-md shadow-slate-900/20 ring-1 ring-slate-900/5 overflow-hidden">
                    <img src="{{ asset('images/UTS.png') }}"
                        alt="UTS Logo"
                        class="h-9 w-9 object-contain">
                </div>
                <div>
                    <p class="text-[10px] font-semibold tracking-[0.18em] text-slate-400 uppercase">
                        UTS Portal
                    </p>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Welcome back
                    </h2>
                    <p class="text-[11px] text-slate-500">
                        Sign in to access your dashboard
                    </p>
                </div>
            </div>

            {{-- error --}}
            @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-3 py-2 text-[11px] text-rose-600">
                {{ $errors->first() }}
            </div>
            @endif

            {{-- form --}}
            <form method="POST" action="{{ url('/login') }}" autocomplete="off" class="space-y-4">
                @csrf

                {{-- email --}}
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-slate-700">Email</label>
                    <div
                        class="flex items-center gap-2 rounded-full border border-slate-200/80
                               bg-transparent px-3 py-2
                               focus-within:border-utsGold focus-within:ring-2 focus-within:ring-utsGold/40">
                        <i class="fa-regular fa-envelope text-[11px] text-slate-400"></i>
                        <input
                            type="email"
                            name="email"
                            required
                            value="{{ old('email') }}"
                            class="w-full border-none bg-transparent text-[12px] text-slate-800
                                   placeholder-slate-400 focus:outline-none focus:ring-0"
                            placeholder="you@example.com">
                    </div>
                </div>

                {{-- password --}}
                <div class="space-y-1.5">
                    <label class="text-[11px] font-medium text-slate-700">Password</label>
                    <div
                        class="flex items-center gap-2 rounded-full border border-slate-200/80
                               bg-transparent px-3 py-2
                               focus-within:border-utsGold focus-within:ring-2 focus-within:ring-utsGold/40">
                        <i class="fa-solid fa-key text-[11px] text-slate-400"></i>
                        <input
                            type="password"
                            name="password"
                            required
                            class="w-full border-none bg-transparent text-[12px] text-slate-800
                                   placeholder-slate-400 focus:outline-none focus:ring-0"
                            placeholder="Enter your password">
                    </div>
                </div>

                {{-- remember --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-600">
                        <input
                            type="checkbox"
                            name="remember"
                            class="h-[13px] w-[13px] rounded border border-slate-300
                                   text-utsGold focus:ring-utsGold/50">
                        <span>Remember me</span>
                    </label>
                </div>

                {{-- primary button (no border) --}}
                <button
                    type="submit"
                    class="mt-2 inline-flex w-full items-center justify-center rounded-full border-0
                           bg-gradient-to-r from-utsGold to-amber-400 px-4 py-2.5 text-[12px]
                           font-semibold text-slate-900 shadow-md shadow-amber-300/40
                           hover:from-amber-400 hover:to-utsGold
                           focus:outline-none focus:ring-2 focus:ring-amber-300
                           focus:ring-offset-2 focus:ring-offset-white transition">
                    Sign in
                </button>

                {{-- Back to Home --}}
                <div class="mt-2 flex justify-center">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center justify-center rounded-full
                              border border-slate-200 bg-white/80 px-6 py-2 text-[11px]
                              font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition">
                        Back to Home
                    </a>
                </div>

                {{-- bottom switch --}}
                <div class="pt-1 text-center text-[11px] text-slate-500">
                    <span>Don’t have an account?</span>
                    <a href="{{ route('register') }}"
                       class="ml-1 font-semibold text-utsGold hover:text-amber-500">
                        Create an account
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection