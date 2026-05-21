<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\LetterGameController;
use App\Http\Controllers\LeaderboardsController;
use App\Http\Controllers\NumberGameController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/play', [PlayController::class, 'index'])->name('play');
Route::get('/leaderboards', LeaderboardsController::class)->name('leaderboards');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/daily-challenges', [DailyChallengeController::class, 'index'])->name('daily-challenges.index');
    Route::get('/daily-challenges/{dailyChallenge}', [DailyChallengeController::class, 'show'])->name('daily-challenges.show');
    Route::post('/daily-challenges/{dailyChallenge}/submit', [DailyChallengeController::class, 'submit'])->name('daily-challenges.submit');
    Route::get('/play/letters/{ageGroup}', [LetterGameController::class, 'show'])->name('play.letters.show');
    Route::post('/play/letters/{ageGroup}/submit', [LetterGameController::class, 'submit'])->name('play.letters.submit');
    Route::get('/play/numbers/{ageGroup}', [NumberGameController::class, 'show'])->name('play.numbers.show');
    Route::post('/play/numbers/{ageGroup}/submit', [NumberGameController::class, 'submit'])->name('play.numbers.submit');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
