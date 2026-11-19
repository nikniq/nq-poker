@extends('layouts.app')
@section('title', 'Log in · Poker')
@section('content')
<section class="panel narrow">
    <h1>Log in</h1>
    @include('auth.partials.oauth')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> Remember me</label>
        <button class="btn">Log in</button>
    </form>
    <p>No account? <a href="{{ route('register') }}">Register</a></p>
</section>
@endsection
