<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Poker')</title>
    <link rel="stylesheet" href="{{ asset('css/poker.css') }}">
</head>
<body>
<header class="nav">
    <a href="{{ route('game') }}" class="brand">♠ Poker</a>
    <nav>
        <a href="{{ route('leaderboard') }}">Leaderboard</a>
        @auth
            <a href="{{ route('history') }}">My Scores</a>
            <span class="who">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button class="link">Log out</button></form>
        @else
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('register') }}" class="btn small">Register</a>
        @endauth
    </nav>
</header>
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
