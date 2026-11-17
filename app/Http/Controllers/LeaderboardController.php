<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index()
    {
        $leaders = User::query()
            ->withCount('scores as hands_played')
            ->withSum('scores as total_won', 'payout')
            ->withSum('scores as total_bet', 'bet')
            ->has('scores')
            ->orderByDesc('best_credits')
            ->limit(20)
            ->get();

        return view('leaderboard', compact('leaders'));
    }

    public function mine(Request $request)
    {
        $user = $request->user();
        $scores = $user->scores()->latest()->paginate(25);

        return view('history', compact('user', 'scores'));
    }
}
