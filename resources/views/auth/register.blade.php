@extends('layouts.app')
@section('title','Register')

@section('content')
<div class="auth-wrap auth-theme">
  <div class="auth-card">
    <div class="auth-head">
      <img class="auth-logo" src="{{ asset('images/UTS.png') }}" alt="UTS Logo">
      <div class="auth-titleblock">
        <h2 class="auth-title">Create your account</h2>
        <p class="auth-subtitle">Join Universal Trade Services</p>
      </div>
    </div>

    @if ($errors->any())
      <div class="auth-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ url('/register') }}" autocomplete="off">
      @csrf
      <div class="auth-field">
        <label>Name</label>
        <input type="text" name="name" required value="{{ old('name') }}">
      </div>
      <div class="auth-field">
        <label>Email</label>
        <input type="email" name="email" required value="{{ old('email') }}">
      </div>
      <div class="auth-field">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <div class="auth-field">
        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" required>
      </div>

      <div class="auth-actions">
        <button type="submit" class="auth-btn">Create Account</button>
        <a href="{{ route('login') }}" class="auth-btn-outline">Go to Login</a>
        <a href="{{ route('home') }}" class="auth-btn-ghost">Back to Home</a>
      </div>
    </form>
  </div>
</div>
@endsection
