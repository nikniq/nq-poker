@extends('layouts.app')

@section('title', 'How to Play — Poker')

@section('content')
<section class="panel howto">
    <h1>How to Play Jacks or Better</h1>
    <p>This is <strong>video poker</strong>: you play alone against a paytable, not against other players. Your goal is to finish with the best five-card poker hand you can. Any pair of Jacks or higher pays.</p>

    <h2>A round, step by step</h2>
    <ol>
        <li><strong>Pick your bet</strong>: 1 to 5 credits, using <kbd>−</kbd> / <kbd>+</kbd> or <em>Max</em>. The highlighted paytable column shows what each hand pays at that bet.</li>
        <li><strong>Press Deal.</strong> Your bet comes out of your credits and you get five cards.</li>
        <li><strong>Choose cards to hold.</strong> Tap or click a card (or press <kbd>1</kbd>–<kbd>5</kbd>) to mark it <em>HELD</em>. Tap it again to let it go.</li>
        <li><strong>Press Draw.</strong> Every card you didn't hold is swapped for a new one from the same deck. You get one draw per round.</li>
        <li><strong>Get paid.</strong> If your final hand is on the paytable, you win your bet × the payout. Otherwise the bet is lost.</li>
    </ol>
    <p class="tip">Out of credits? The Deal button becomes <strong>Refill</strong> and gives you a fresh {{ 100 }} credits. Log in to keep your credits and get on the leaderboard.</p>

    <p class="tip">Want a bigger table? Press <strong>⛶ Full screen</strong> above the paytable (or <kbd>F</kbd>). Press it again or <kbd>Esc</kbd> to exit.</p>

    <h2>Winning hands (best to worst)</h2>
    <table class="ranks">
        @php
            $examples = [
                'Royal Flush'     => ['A♠','K♠','Q♠','J♠','10♠'],
                'Straight Flush'  => ['9♥','8♥','7♥','6♥','5♥'],
                'Four of a Kind'  => ['Q♣','Q♦','Q♥','Q♠','4♦'],
                'Full House'      => ['8♠','8♦','8♣','K♥','K♠'],
                'Flush'           => ['A♦','J♦','8♦','6♦','2♦'],
                'Straight'        => ['10♣','9♦','8♠','7♥','6♣'],
                'Three of a Kind' => ['7♠','7♥','7♦','K♣','2♠'],
                'Two Pair'        => ['J♥','J♣','4♠','4♦','9♣'],
                'Jacks or Better' => ['K♠','K♦','9♥','5♣','2♠'],
            ];
            $desc = [
                'Royal Flush'     => 'A, K, Q, J, 10, all the same suit.',
                'Straight Flush'  => 'Five cards in a row, all the same suit.',
                'Four of a Kind'  => 'Four cards of the same rank.',
                'Full House'      => 'Three of a kind plus a pair.',
                'Flush'           => 'Any five cards of the same suit.',
                'Straight'        => 'Five cards in a row, mixed suits. An Ace can be high (10-J-Q-K-A) or low (A-2-3-4-5).',
                'Three of a Kind' => 'Three cards of the same rank.',
                'Two Pair'        => 'Two different pairs.',
                'Jacks or Better' => 'One pair of Jacks, Queens, Kings or Aces. A pair of 10s or lower does not pay.',
            ];
            $used = ['Four of a Kind' => 4, 'Three of a Kind' => 3, 'Two Pair' => 4, 'Jacks or Better' => 2];
        @endphp
        <tr><th>Hand</th><th>Example</th><th>Pays (per credit)</th></tr>
        @foreach ($paytable as $name => $mult)
            <tr>
                <td><strong>{{ $name }}</strong><br><small>{{ $desc[$name] ?? '' }}</small></td>
                <td><span class="mini">
                    @foreach ($examples[$name] ?? [] as $i => $c)
                        <span class="{{ preg_match('/[♥♦]/u', $c) ? 'r' : '' }} {{ isset($used[$name]) && $i >= $used[$name] ? 'dim' : '' }}">{{ $c }}</span>
                    @endforeach
                </span></td>
                <td>{{ $mult }}×{{ $name === 'Royal Flush' ? ' (4000 at max bet)' : '' }}</td>
            </tr>
        @endforeach
    </table>
    <p><small>Faded cards don't count toward the hand.</small></p>

    <h2>Double Up (optional)</h2>
    <p>After any winning hand you can gamble your win. Pick <strong>Red</strong> or <strong>Black</strong> (or press <kbd>R</kbd> / <kbd>B</kbd>), and the next card is turned over:</p>
    <ul>
        <li><strong>Right colour:</strong> your win doubles, and you can double again, up to 5 times in a row.</li>
        <li><strong>Wrong colour:</strong> you lose that win, but not your original bet credits.</li>
        <li>Press <strong>Collect</strong> (<kbd>C</kbd>) or simply deal the next hand to keep your win.</li>
    </ul>
    <p class="tip">It's a 50/50 guess every time, so it doesn't change your long-run odds. It just makes each win riskier and more exciting.</p>

    <h2>Strategy tips</h2>
    <p class="tip">New to the game? Leave <strong>Hints</strong> switched on (top of the table). After each deal, a dashed outline shows which cards to hold, with a short reason why. Once you're making the same choices yourself, switch hints off with the toggle or press <kbd>H</kbd>.</p>
    <ul>
        <li><strong>Always bet 5 credits if you can afford it.</strong> A Royal Flush pays 4000 at max bet instead of 1250, which is the biggest swing in the game.</li>
        <li><strong>Never break up a paying hand</strong> (except to chase a Royal Flush when you already have four of its cards).</li>
        <li><strong>Hold any high pair</strong> (J, Q, K, A). A low pair (2s to 10s) is still worth holding, because it can turn into Two Pair, Three of a Kind or better.</li>
        <li><strong>Four to a flush or an open-ended straight?</strong> Hold the four and draw one.</li>
        <li><strong>Nothing at all?</strong> Hold your Jacks, Queens, Kings and Aces (up to two). If you have none, draw five new cards.</li>
        <li>Don't hold a "kicker". Keeping an extra high card next to a pair lowers your chances of improving.</li>
    </ul>

    <p style="text-align:center;margin-top:28px"><a href="{{ route('game') }}" class="btn big">Play now</a></p>
</section>
@endsection
