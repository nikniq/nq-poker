@extends('layouts.app')
@section('title', 'Sign up · NQ Poker')
@section('content')
<section class="panel narrow auth">
    @include('auth.partials.tabs')
    <h1>Create your account</h1>
    <ul class="perks">
        <li>Your credits are saved between visits</li>
        <li>Every hand recorded in My Scores</li>
        <li>Your name on the leaderboard</li>
    </ul>
    @include('auth.partials.oauth')
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Player name <input name="name" value="{{ old('name') }}" required autofocus maxlength="50" autocomplete="nickname"></label>
        <small class="muted">Shown on the leaderboard.</small>
        @error('name')<div class="error">{{ $message }}</div>@enderror
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <small class="muted">At least 8 characters.</small>
        @error('password')<div class="error">{{ $message }}</div>@enderror
        <label>Confirm password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="btn block">Create account</button>
    </form>
    <p class="switch-line">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</section>
@endsection
