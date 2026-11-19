@extends('layouts.app')
@section('title', 'Register · Poker')
@section('content')
<section class="panel narrow">
    <h1>Register</h1>
    <p>Create an account to save your scores and appear on the leaderboard.</p>
    @include('auth.partials.oauth')
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Player name <input name="name" value="{{ old('name') }}" required autofocus></label>
        @error('name')<div class="error">{{ $message }}</div>@enderror
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required></label>
        @error('password')<div class="error">{{ $message }}</div>@enderror
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        <button class="btn">Create account</button>
    </form>
    <p>Already registered? <a href="{{ route('login') }}">Log in</a></p>
</section>
@endsection
