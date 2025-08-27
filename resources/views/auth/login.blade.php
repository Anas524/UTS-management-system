@extends('layouts.app')
@section('title','Login')

@section('content')
<div class="auth-wrap auth-theme">
    <div class="auth-card">
        <div class="auth-head">
            <img class="auth-logo" src="{{ asset('images/UTS.png') }}" alt="UTS Logo">
            <div class="auth-titleblock">
                <h2 class="auth-title">Welcome back</h2>
                <p class="auth-subtitle">Sign in to continue</p>
            </div>
        </div>

        @if ($errors->any())
        <div class="auth-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}" autocomplete="off">
            @csrf
            <div class="auth-field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="auth-row">
                <label class="auth-check">
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>

            <button type="submit" class="auth-btn">Sign in</button>

            <div class="auth-actions">
                <a href="{{ route('home') }}" class="auth-btn-outline">Back to Home</a>
            </div>

            <div class="auth-switch">
                <span>Don’t have an account?</span>
                <a href="{{ route('register') }}">Create an account</a>
            </div>
        </form>
    </div>
</div>
@endsection