@extends('layouts.app')

@section('content')
<section class="table">
    @guest
        <p class="notice">Playing as guest. <a href="{{ route('register') }}">Register</a> or <a href="{{ route('login') }}">log in</a> to save your scores.</p>
    @endguest

    <div class="table-top">
        <label class="switch" title="Suggests which cards to hold. Turn off once you know the strategy. (H)">
            <input type="checkbox" id="hintToggle" checked><span class="slider"></span> Hints
        </label>
        <a href="{{ route('how-to-play') }}" class="btn small ghost">? How to play</a></div>

    <table class="paytable">
        <tr><th>Hand</th>@for ($b = 1; $b <= 5; $b++)<th data-bet-col="{{ $b }}">{{ $b }}</th>@endfor</tr>
        @foreach ($paytable as $name => $mult)
            <tr data-hand="{{ $name }}"><td>{{ $name }}</td>
                @for ($b = 1; $b <= 5; $b++)
                    <td data-bet-col="{{ $b }}">{{ $name === 'Royal Flush' && $b === 5 ? 4000 : $mult * $b }}</td>
                @endfor
            </tr>
        @endforeach
    </table>

    <div id="message" class="message">Choose your bet and press Deal.</div>

    <div id="hint" class="hint-box" hidden></div>

    <div id="hand" class="hand">
        @for ($i = 0; $i < 5; $i++)
            <div class="card back" data-index="{{ $i }}"><span class="held">HELD</span></div>
        @endfor
    </div>

    <div class="controls">
        <div class="meter">Credits <strong id="credits">{{ $credits }}</strong></div>
        <div class="bet meter">
            Bet <button id="betDown" class="btn small">−</button>
            <strong id="bet">1</strong>
            <button id="betUp" class="btn small">+</button>
            <button id="betMax" class="btn small">Max</button>
        </div>
        <button id="action" class="btn big">Deal</button>
    </div>
    <p class="keys">Keys: <kbd>1</kbd>–<kbd>5</kbd> hold &nbsp; <kbd>Space</kbd> deal/draw &nbsp; <kbd>+</kbd>/<kbd>−</kbd> bet &nbsp; <kbd>H</kbd> hints</p>
</section>
@endsection

@push('scripts')
<script>
window.POKER = {
    credits: {{ $credits }},
    routes: { deal: @json(route('game.deal')), draw: @json(route('game.draw')), reset: @json(route('game.reset')) }
};
</script>
<script src="{{ asset('js/hints.js') }}"></script>
<script src="{{ asset('js/poker.js') }}"></script>
@endpush
