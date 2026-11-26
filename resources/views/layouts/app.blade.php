<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Free Jacks or Better video poker. Play as a guest or sign in to save your scores and climb the leaderboard.">
    <title>@yield('title', 'Poker · Jacks or Better')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>♠</text></svg>">
    <link rel="stylesheet" href="{{ asset('css/poker.css') }}">
</head>
<body>
@php
    $links = [
        ['game', 'Play'],
        ['how-to-play', 'How to Play'],
        ['leaderboard', 'Leaderboard'],
    ];
    $portal = rtrim((string) config('services.nikniq.url', 'https://nikniq.com'), '/');
@endphp
<div class="portal-bar">
    <a href="{{ $portal }}" class="portal-back">← Back to <strong>nikniq.com</strong></a>
    <span class="portal-tag">More free games at nikniq.com</span>
</div>
<header class="nav">
    <a href="{{ route('game') }}" class="brand"><span class="brand-mark">♠</span> NQ Poker</a>

    <input type="checkbox" id="menu" class="menu-check" aria-hidden="true">
    <label for="menu" class="menu-btn" aria-label="Open menu"><span></span><span></span><span></span></label>

    <nav class="menu" aria-label="Main">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}" @class(['active' => request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach

        <a href="{{ $portal }}" class="menu-portal" title="More games on nikniq.com"><span class="nq-logo" aria-hidden="true">nq</span> nikniq.com</a>

        @auth
            <details class="user">
                <summary>
                    @if (auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="" class="avatar" referrerpolicy="no-referrer">
                    @else
                        <span class="avatar initial">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <span class="who">{{ auth()->user()->name }}</span>
                </summary>
                <div class="user-menu">
                    <div class="user-credits">Credits <strong>{{ auth()->user()->credits }}</strong></div>
                    <a href="{{ route('history') }}" @class(['active' => request()->routeIs('history')])>My Scores</a>
                    @if (auth()->user()->nikniq_id)
                        <a href="{{ $portal }}">My nikniq.com account ↗</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="link">Log out</button></form>
                </div>
            </details>
        @else
            <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login')])>Log in</a>
            <a href="{{ route('register') }}" class="btn small">Sign up free</a>
        @endauth
    </nav>
</header>

<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @yield('content')
</main>

<footer class="footer">
    <div class="footer-inner">
        <div class="footer-col">
            <a href="{{ route('game') }}" class="brand"><span class="brand-mark">♠</span> NQ Poker</a>
            <p>Free Jacks or Better video poker. Part of the <a href="{{ $portal }}">nikniq.com</a> games family.</p>
            <a href="{{ $portal }}" class="btn small btn-portal"><span class="nq-logo" aria-hidden="true">nq</span> Back to nikniq.com</a>
        </div>
        <div class="footer-col">
            <h4>Play</h4>
            <a href="{{ route('game') }}">Play now</a>
            <a href="{{ route('how-to-play') }}">How to play</a>
            <a href="{{ route('leaderboard') }}">Leaderboard</a>
        </div>
        <div class="footer-col">
            <h4>Account</h4>
            @auth
                <a href="{{ route('history') }}">My scores</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="link">Log out</button></form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Create account</a>
            @endauth
        </div>
        <div class="footer-col">
            <h4>More</h4>
            <a href="{{ $portal }}">nikniq.com home</a>
            <a href="{{ $portal }}">More games</a>
        </div>
    </div>
    <p class="footer-note">© {{ date('Y') }} <a href="{{ $portal }}">nikniq.com</a> · For entertainment only. Credits have no cash value and no real money is wagered.</p>
</footer>
@stack('scripts')
</body>
</html>
