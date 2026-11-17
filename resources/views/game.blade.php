@extends('layouts.app')

@section('content')
<section class="table">
    @guest
        <p class="notice">Playing as guest. <a href="{{ route('register') }}">Register</a> or <a href="{{ route('login') }}">log in</a> to save your scores.</p>
    @endguest

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

    <div id="hand" class="hand">
        @for ($i = 0; $i < 5; $i++)
            <div class="card back" data-index="{{ $i }}"><span class="held">HELD</span></div>
        @endfor
    </div>

    <div class="controls">
        <div>Credits: <strong id="credits">{{ $credits }}</strong></div>
        <div class="bet">
            Bet: <button id="betDown" class="btn small">−</button>
            <strong id="bet">1</strong>
            <button id="betUp" class="btn small">+</button>
            <button id="betMax" class="btn small">Max</button>
        </div>
        <button id="action" class="btn big">Deal</button>
    </div>
</section>
@endsection

@push('scripts')
<script>
window.POKER = {
    credits: {{ $credits }},
    routes: { deal: @json(route('game.deal')), draw: @json(route('game.draw')), reset: @json(route('game.reset')) }
};
</script>
<script src="{{ asset('js/poker.js') }}"></script>
@endpush
