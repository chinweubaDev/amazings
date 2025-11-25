<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController; // Correct controller name

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/match-winner', [HomeController::class, 'showmatchwinner'])->name('match.winner');
Route::get('/double-chance', [HomeController::class, 'showdouble'])->name('double.chance');
Route::get('/both-teams-score', [HomeController::class, 'showbothteamtoscore'])->name('btts');
Route::get('/over-under-2-5', [HomeController::class, 'showoverunder25'])->name('over.under.25');
Route::get('/halftime-fulltime', [HomeController::class, 'htft'])->name('htft');
