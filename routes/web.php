<?php

use App\Http\Controllers\LetterGameController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/play', [PlayController::class, 'index'])->name('play');
Route::view('/leaderboards', 'leaderboards')->name('leaderboards');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/play/letters/{ageGroup}', [LetterGameController::class, 'show'])->name('play.letters.show');
    Route::post('/play/letters/{ageGroup}/submit', [LetterGameController::class, 'submit'])->name('play.letters.submit');
    Route::view('/profile', 'profile.show')->name('profile.show');
    Route::get('/profile/settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
