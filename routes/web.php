<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('game');
Route::post('/game/deal', [GameController::class, 'deal'])->name('game.deal');
Route::post('/game/draw', [GameController::class, 'draw'])->name('game.draw');
Route::post('/game/reset', [GameController::class, 'reset'])->name('game.reset');

Route::view('/how-to-play', 'how-to-play', ['paytable' => \App\Poker\HandEvaluator::PAYTABLE])->name('how-to-play');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/history', [LeaderboardController::class, 'mine'])->name('history');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
