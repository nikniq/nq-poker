<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NikniqController;
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
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->middleware('throttle:20,1')->name('auth.google.callback');
    Route::get('/auth/nikniq', [NikniqController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.nikniq');
    Route::get('/auth/nikniq/callback', [NikniqController::class, 'callback'])->middleware('throttle:20,1')->name('auth.nikniq.callback');
    // The portal's default registered redirect URI for every game.
    Route::get('/auth/callback', [NikniqController::class, 'callback'])->middleware('throttle:20,1')->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/history', [LeaderboardController::class, 'mine'])->name('history');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
