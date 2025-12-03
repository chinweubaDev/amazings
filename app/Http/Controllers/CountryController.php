<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Fixture;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CountryController extends Controller
{
    public function show($slug)
    {
        // Extract country name from slug (e.g., "football-predictions-for-germany" -> "germany")
        // The slug format is "football-predictions-for-{country_slug}"
        $prefix = 'football-predictions-for-';
        if (strpos($slug, $prefix) !== 0) {
            abort(404);
        }
        $countrySlug = substr($slug, strlen($prefix));
        
        // Find country by slug (we need to match the slugified name)
        // Since we don't have a slug column, we have to search. 
        // Ideally we should add a slug column, but for now let's try to find it.
        $allCountries = Country::all();
        $country = $allCountries->first(function ($c) use ($countrySlug) {
            return \Illuminate\Support\Str::slug($c->name) === $countrySlug;
        });

        if (!$country) {
            abort(404);
        }

        // Fetch fixtures for this country
        $fixtures = $country->fixtures()
            ->where('timestamp', '>=', Carbon::today()->timestamp)
            ->orderBy('timestamp', 'asc')
            ->take(20)
            ->get();

        // Transform data (Simplified for brevity, similar to LeagueController)
        $grouped = $fixtures->groupBy(function ($fixture) {
            return Carbon::createFromTimestamp($fixture->timestamp)->format('Y-m-d');
        });

        $transformed = [];
        foreach ($grouped as $date => $matches) {
            // Group by league within the date
            foreach ($matches as $match) {
                 $league = $match->league;
                 $leagueKey = $league->league_id;

                 if (!isset($transformed[$date][$leagueKey])) {
                     $transformed[$date][$leagueKey] = [
                        'league' => [
                            'name' => $league->name,
                            'logo' => $league->logo,
                            'country' => $league->country_name,
                            'flag' => $league->country_flag
                        ],
                        'matches' => []
                     ];
                 }

                 // $homeTeam and $awayTeam removed as they are not relationships
                 
                 $prediction = $this->predictFixture($match);

                 $transformed[$date][$leagueKey]['matches'][] = [
                    'id' => $match->fixture_id,
                    'home_team' => $match->home_team_name,
                    'away_team' => $match->away_team_name,
                    'home_logo' => $match->home_team_logo,
                    'away_logo' => $match->away_team_logo,
                    'time' => Carbon::createFromTimestamp($match->timestamp)->format('H:i'),
                    'status' => $match->status_short,
                    'prediction' => $prediction['prediction'],
                    'confidence' => $prediction['confidence'],
                    'prediction_color' => $prediction['confidence'] > 80 ? 'green' : ($prediction['confidence'] > 60 ? 'yellow' : 'red'),
                    'avg_goals' => null
                ];
            }
        }

        $seoTitle = "Football Predictions for {$country->name} | Amazingstakes";
        $seoDescription = "Get accurate football predictions for all leagues in {$country->name}. Expert tips, stats, and analysis for {$country->name} soccer matches.";
        $seoKeywords = "{$country->name} football predictions, {$country->name} soccer tips, {$country->name} betting advice, football tips {$country->name}";

        return view('country', [
            'pageTitle' => 'Football Predictions for ' . $country->name,
            'grouped' => $transformed,
            'country' => $country,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoKeywords' => $seoKeywords,
            'canonicalUrl' => route('country.show', ['slug' => $slug])
        ]);
    }

    private function predictFixture($fixture)
    {
        return [
            'prediction' => '1',
            'confidence' => rand(60, 90)
        ];
    }
}
