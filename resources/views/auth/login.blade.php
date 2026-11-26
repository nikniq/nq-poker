@extends('layouts.app')
@section('title', 'Log in · NQ Poker')
@section('content')
<section class="panel narrow auth">
    @include('auth.partials.tabs')
    <h1>Welcome back</h1>
    <p class="muted">Log in to keep your credits and climb the leaderboard.</p>
    @include('auth.partials.oauth')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
        @error('password')<div class="error">{{ $message }}</div>@enderror
        <label class="check"><input type="checkbox" name="remember" checked> Remember me</label>
        <button class="btn block">Log in</button>
    </form>
    <p class="switch-line">New here? <a href="{{ route('register') }}">Create a free account</a> · or <a href="{{ route('game') }}">play as a guest</a></p>
</section>
@endsection
