<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share data with sideleft view
        \Illuminate\Support\Facades\View::composer('partials.sideleft', function ($view) {
            $topLeagues = \App\Models\League::where('is_top_league', true)->get();
            $countries = \App\Models\Country::with(['leagues' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();

            $view->with('topLeagues', $topLeagues);
            $view->with('countries', $countries);
        });
    }
}
