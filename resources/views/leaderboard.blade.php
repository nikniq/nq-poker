@extends('layouts.app')
@section('title', 'Leaderboard · Poker')
@section('content')
<section class="panel">
    <h1>Leaderboard</h1>
    <table class="grid">
        <tr><th>#</th><th>Player</th><th>Best credits</th><th>Current</th><th>Hands</th><th>Net</th></tr>
        @forelse ($leaders as $i => $u)
            <tr @if(auth()->id() === $u->id) class="me" @endif>
                <td>{{ $i + 1 }}</td><td>{{ $u->name }}</td><td>{{ $u->best_credits }}</td>
                <td>{{ $u->credits }}</td><td>{{ $u->hands_played }}</td>
                <td>{{ ($u->total_won ?? 0) - ($u->total_bet ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No scores yet. Be the first!</td></tr>
        @endforelse
    </table>
</section>
@endsection
