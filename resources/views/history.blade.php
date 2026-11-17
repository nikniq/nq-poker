@extends('layouts.app')
@section('title', 'My Scores · Poker')
@section('content')
<section class="panel">
    <h1>My Scores</h1>
    <p>Credits: <strong>{{ $user->credits }}</strong> · Best: <strong>{{ $user->best_credits }}</strong> · Hands played: <strong>{{ $scores->total() }}</strong></p>
    <table class="grid">
        <tr><th>When</th><th>Hand</th><th>Bet</th><th>Payout</th><th>Credits after</th></tr>
        @forelse ($scores as $s)
            <tr><td>{{ $s->created_at->diffForHumans() }}</td><td>{{ $s->hand }}</td><td>{{ $s->bet }}</td><td>{{ $s->payout }}</td><td>{{ $s->credits_after }}</td></tr>
        @empty
            <tr><td colspan="5">No hands played yet.</td></tr>
        @endforelse
    </table>
    {{ $scores->links() }}
</section>
@endsection
