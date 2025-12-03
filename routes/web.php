<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController; // Correct controller name
use App\Http\Controllers\DoubleChanceController;
use App\Http\Controllers\MatchWinnerController;
use App\Http\Controllers\BothTeamsScoreController;
use App\Http\Controllers\OverUnder25Controller;
use App\Http\Controllers\HalfTimeFullTimeController;
use App\Http\Controllers\LivePredictionController;
use App\Http\Controllers\PageController;




Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/yesterday-prediction', [HomeController::class, 'yesterday'])->name('yesterday');
Route::get('/tomorrow-prediction', [HomeController::class, 'tomorrow'])->name('tomorrow');
Route::get('/weekend-prediction', [HomeController::class, 'weekend'])->name('weekend');
Route::get('/upcoming-popular-matches', [HomeController::class, 'upcoming'])->name('upcoming');
Route::get('/must-win-teams-today', [HomeController::class, 'mustWin'])->name('must.win');

// Redirects for side nav compatibility
Route::redirect('/yesterday-predictions', '/yesterday-prediction');
Route::redirect('/tomorrow-predictions', '/tomorrow-prediction');
Route::redirect('/weekend-football-prediction', '/weekend-prediction');

// New Pages from side nav
Route::get('/tips180', [HomeController::class, 'tips180'])->name('tips180');
Route::get('/victor-predict', [HomeController::class, 'victorPredict'])->name('victor.predict');
Route::get('/jackpot-predictions', [HomeController::class, 'jackpot'])->name('jackpot');
Route::get('/top-trends', [HomeController::class, 'trends'])->name('trends');
Route::get('/match-winner', [MatchWinnerController::class, 'index'])->name('match.winner');
Route::get('/double-chance', [DoubleChanceController::class, 'index'])->name('double.chance');
Route::get('/both-teams-score', [BothTeamsScoreController::class, 'index'])->name('btts');
Route::get('/todays-prediction/predictions-under-over', [OverUnder25Controller::class, 'index'])->name('over.under.25');
Route::get('/halftime-fulltime', [HalfTimeFullTimeController::class, 'index'])->name('htft');
Route::get('/live-predictions', [LivePredictionController::class, 'index'])->name('live.predictions');
Route::get('/ajax/live-predictions', [LivePredictionController::class, 'fetchLive'])->name('ajax.live.predictions');

// Dynamic League and Country Routes
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\CountryController;

Route::get('/league/{slug}/fixtures', [LeagueController::class, 'show'])->name('league.show')->where('slug', '.*');
Route::get('/country/{slug}/fixtures', [CountryController::class, 'show'])->name('country.show');

// Static Pages
Route::get('/contact-us', [PageController::class, 'contact'])->name('contact');
Route::get('/terms-and-conditions', [PageController::class, 'terms'])->name('terms');
Route::get('/faqs', [PageController::class, 'faqs'])->name('faqs');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/about-us', [PageController::class, 'about'])->name('about');
Route::get('/refund-policy', [PageController::class, 'refund'])->name('refund');
Route::get('/partners', [PageController::class, 'partners'])->name('partners');
