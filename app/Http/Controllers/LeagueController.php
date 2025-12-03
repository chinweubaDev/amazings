<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Fixture;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LeagueController extends Controller
{
    public function show($slug)
    {
        // Extract ID from slug (e.g., "bundesliga-78")
        if (!preg_match('/-(\d+)$/', $slug, $matches)) {
            abort(404);
        }
        $leagueId = $matches[1];

        $league = League::where('league_id', $leagueId)->firstOrFail();

        // Fetch fixtures for this league (Today, Tomorrow, etc.)
        // For simplicity, let's show upcoming fixtures for this league
        $fixtures = Fixture::where('league_id', $leagueId)
            ->where('timestamp', '>=', Carbon::today()->timestamp)
            ->orderBy('timestamp', 'asc')
            ->take(20)
            ->get();

        // Reuse home view or create a new one. Let's reuse home for now but pass specific data
        // We need to group them by date for the home view to work, or create a simpler view.
        // Let's create a simpler view structure or adapt the data.
        
        $grouped = $fixtures->groupBy(function ($fixture) {
            return Carbon::createFromTimestamp($fixture->timestamp)->format('Y-m-d');
        });

        // Transform data to match home view expectation
        $transformed = [];
        foreach ($grouped as $date => $matches) {
            foreach ($matches as $match) {
                // ... (Logic similar to HomeController to prepare match data)
                // For now, let's just pass the raw fixtures and handle them in a new view 'league.blade.php'
                // Or better, let's use the same transformation logic.
                
                // $homeTeam and $awayTeam removed as they are not relationships
                
                $prediction = $this->predictFixture($match);

                $transformed[$date]['league'] = [
                    'name' => $league->name,
                    'logo' => $league->logo,
                    'country' => $league->country_name,
                    'flag' => $league->country_flag
                ];
                
                $transformed[$date]['matches'][] = [
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
                    'avg_goals' => null // Placeholder
                ];
            }
        }

        $seoTitle = "Football Predictions for {$league->name} ({$league->country_name}) | Amazingstakes";
        $seoDescription = "Get accurate {$league->name} football predictions, stats, and tips. We cover all matches in {$league->country_name} with high confidence predictions.";
        $seoKeywords = "{$league->name} predictions, {$league->name} tips, {$league->country_name} football, {$league->name} betting tips, soccer predictions {$league->country_name}";

        return view('league', [
            'pageTitle' => 'Football Predictions for ' . $league->name,
            'grouped' => $transformed,
            'league' => $league,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoKeywords' => $seoKeywords,
            'canonicalUrl' => route('league.show', ['slug' => $slug])
        ]);
    }

    private function predictFixture($fixture)
    {
        // Simplified prediction logic for now
        return [
            'prediction' => '1',
            'confidence' => rand(60, 90)
        ];
    }
}
