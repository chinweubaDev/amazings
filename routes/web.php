<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController; // Correct controller name
use App\Http\Controllers\DoubleChanceController;
use App\Http\Controllers\MatchWinnerController;
use App\Http\Controllers\BothTeamsScoreController;
use App\Http\Controllers\OverUnder25Controller;
use App\Http\Controllers\HalfTimeFullTimeController;
use App\Http\Controllers\LivePredictionController;




Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/match-winner', [MatchWinnerController::class, 'index'])->name('match.winner');
Route::get('/double-chance', [DoubleChanceController::class, 'index'])->name('double.chance');
Route::get('/both-teams-score', [BothTeamsScoreController::class, 'index'])->name('btts');
Route::get('/todays-prediction/predictions-under-over', [OverUnder25Controller::class, 'index'])->name('over.under.25');
Route::get('/halftime-fulltime', [HalfTimeFullTimeController::class, 'index'])->name('htft');
Route::get('/live-predictions', [LivePredictionController::class, 'index'])->name('live.predictions');
Route::get('/ajax/live-predictions', [LivePredictionController::class, 'fetchLive'])->name('ajax.live.predictions');
