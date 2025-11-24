<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // Fetch today's fixtures with their relationships
        $fixtures = Fixture::with(['league', 'odds'])
            ->whereDate('date', $today)
            ->orderBy('date', 'asc')
            ->get();

        // Transform fixtures into structured data
        $data = $fixtures->map(function ($fixture) {
            // Parse JSON fields if they're stored as strings
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            // Get prediction for this fixture
            $predictionOutcome = $this->predictFixture($fixture, $odds, $h2h, $stats);

            // Get match winner odds
            $matchWinnerOdds = $this->getMatchWinnerOdds($fixture, $odds);

            // Calculate average goals from H2H
            $avgGoals = $this->calculateAverageGoals($h2h);

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->league_country ?? 'Unknown',
                'league' => $fixture->league_name ?? 'Unknown League',
                'country_flag' => $fixture->league_flag,
                'league_logo' => $fixture->league_logo,
                'league_id' => $fixture->league_id,
                'match_date' => $fixture->date,
                'match_time' => Carbon::parse($fixture->date)->format('H:i'),
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $fixture->home_team_logo,
                'away_logo' => $fixture->away_team_logo,
                'prediction' => $predictionOutcome['prediction'],
                'confidence' => $predictionOutcome['confidence'],
                'prediction_color' => $this->getPredictionColor($predictionOutcome['prediction'], $predictionOutcome['confidence']),
                'odds' => $matchWinnerOdds,
                'avg_goals' => $avgGoals,
                'status' => $fixture->status_long ?? 'Scheduled',
                'status_short' => $fixture->status_short ?? 'NS',
                'home_score' => $fixture->goals_home,
                'away_score' => $fixture->goals_away,
                'halftime_home' => $fixture->halftime_home,
                'halftime_away' => $fixture->halftime_away,
                'elapsed' => $fixture->elapsed,
                'venue' => $fixture->venue_name,
                'is_finished' => in_array($fixture->status_short, ['FT', 'AET', 'PEN']),
                'has_started' => !in_array($fixture->status_short, ['NS', 'TBD', 'CANC', 'PST']),
            ];
        });

        // Group by Country - League for organized display
        $grouped = $data->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('home', ['grouped' => $grouped]);
    }

    private function predictFixture($fixture, $odds, $h2h, $stats)
    {
        $prediction = 'Draw';
        $confidence = rand(60, 95);
        $oddValue = null;

        // Handle the new JSON structure
        $matchWinnerData = null;
        
        if (!empty($odds) && is_array($odds)) {
            // Look through the new JSON structure
            foreach ($odds as $oddsEntry) {
                if (isset($oddsEntry['bookmakers']) && is_array($oddsEntry['bookmakers'])) {
                    foreach ($oddsEntry['bookmakers'] as $bookmaker) {
                        if (isset($bookmaker['bets']) && is_array($bookmaker['bets'])) {
                            foreach ($bookmaker['bets'] as $bet) {
                                if (isset($bet['name']) && $bet['name'] === 'Match Winner' && isset($bet['values'])) {
                                    $matchWinnerData = $bet;
                                    break 3; // Break out of all three loops
                                }
                            }
                        }
                    }
                }
            }
            
            // Fallback to old structure if new structure didn't work
            if (!$matchWinnerData) {
                $matchWinnerData = collect($odds)
                    ->pluck('bookmakers')
                    ->flatten(1)
                    ->pluck('bets')
                    ->flatten(1)
                    ->firstWhere('name', 'Match Winner');
            }
        }

        if ($matchWinnerData && isset($matchWinnerData['values'])) {
            $values = $matchWinnerData['values'];
            $weighted = [];

            foreach ($values as $v) {
                $odd = (float) $v['odd'];
                if ($odd > 0) { // Ensure valid odds
                    $weighted[$v['value']] = 1 / $odd;
                }
            }

            if (!empty($weighted)) {
                $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
                $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);

                if ($homeWins > $awayWins && isset($weighted['Home'])) {
                    $weighted['Home'] *= 1.2;
                } elseif ($awayWins > $homeWins && isset($weighted['Away'])) {
                    $weighted['Away'] *= 1.2;
                }

                $prediction = $this->weightedRandom($weighted);

                foreach ($matchWinnerData['values'] as $val) {
                    if ($val['value'] === $prediction) {
                        $oddValue = $val['odd'];
                        break;
                    }
                }
            }
        }

        // Map prediction values to display format
        $displayPrediction = $this->mapPredictionToDisplay($prediction);

        return [
            'prediction' => $displayPrediction,
            'confidence' => $confidence,
            'odd' => $oddValue,
        ];
    }

    /**
     * Map API prediction values to display format
     */
    private function mapPredictionToDisplay($prediction)
    {
        switch ($prediction) {
            case 'Home':
                return '1';
            case 'Draw':
                return 'X';
            case 'Away':
                return '2';
            default:
                return $prediction; // Return as-is for other formats
        }
    }

    private function countH2HWins($h2h, $teamId)
    {
        $wins = 0;
        foreach ($h2h as $match) {
            $homeId = $match['teams']['home']['id'] ?? null;
            $awayId = $match['teams']['away']['id'] ?? null;
            $winner = $match['teams']['home']['winner'] ?? null;

            if (($homeId == $teamId && $winner === true) || ($awayId == $teamId && $winner === true)) {
                $wins++;
            }
        }
        return $wins;
    }

    private function weightedRandom($weights)
    {
        $total = array_sum($weights);
        $rand = mt_rand() / mt_getrandmax();
        $running = 0;
        foreach ($weights as $key => $w) {
            $running += $w / $total;
            if ($rand <= $running) return $key;
        }
        return array_key_first($weights);
    }

    /**
     * Get match winner odds from fixture odds data
     */
    private function getMatchWinnerOdds($fixture, $odds)
    {
        // First try to get from odds relationship if it's loaded and is a collection
        if ($fixture->relationLoaded('odds') && is_object($fixture->odds) && method_exists($fixture->odds, 'isNotEmpty') && $fixture->odds->isNotEmpty()) {
            $homeOdd = $fixture->odds->where('bet_name', 'Match Winner')->where('bet_value', 'Home')->first();
            $drawOdd = $fixture->odds->where('bet_name', 'Match Winner')->where('bet_value', 'Draw')->first();
            $awayOdd = $fixture->odds->where('bet_name', 'Match Winner')->where('bet_value', 'Away')->first();
            
            return [
                'home' => $homeOdd?->odd,
                'draw' => $drawOdd?->odd,
                'away' => $awayOdd?->odd,
            ];
        }

        // Try to get from JSON odds data if available
        if (!empty($odds) && is_array($odds)) {
            // Handle the new JSON structure where odds is an array with bookmakers
            foreach ($odds as $oddsEntry) {
                if (isset($oddsEntry['bookmakers']) && is_array($oddsEntry['bookmakers'])) {
                    foreach ($oddsEntry['bookmakers'] as $bookmaker) {
                        if (isset($bookmaker['bets']) && is_array($bookmaker['bets'])) {
                            foreach ($bookmaker['bets'] as $bet) {
                                if (isset($bet['name']) && $bet['name'] === 'Match Winner' && isset($bet['values'])) {
                                    $oddsArray = ['home' => null, 'draw' => null, 'away' => null];
                                    foreach ($bet['values'] as $value) {
                                        switch ($value['value']) {
                                            case 'Home':
                                            case '1':
                                                $oddsArray['home'] = (float) $value['odd'];
                                                break;
                                            case 'Draw':
                                            case 'X':
                                                $oddsArray['draw'] = (float) $value['odd'];
                                                break;
                                            case 'Away':
                                            case '2':
                                                $oddsArray['away'] = (float) $value['odd'];
                                                break;
                                        }
                                    }
                                    
                                    // If we found valid odds, return them
                                    if ($oddsArray['home'] !== null || $oddsArray['draw'] !== null || $oddsArray['away'] !== null) {
                                        return $oddsArray;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Fallback: Try the old structure as well
            $matchWinner = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Match Winner');

            if ($matchWinner && isset($matchWinner['values'])) {
                $oddsArray = ['home' => null, 'draw' => null, 'away' => null];
                foreach ($matchWinner['values'] as $v) {
                    switch ($v['value']) {
                        case 'Home':
                        case '1':
                            $oddsArray['home'] = (float) $v['odd'];
                            break;
                        case 'Draw':
                        case 'X':
                            $oddsArray['draw'] = (float) $v['odd'];
                            break;
                        case 'Away':
                        case '2':
                            $oddsArray['away'] = (float) $v['odd'];
                            break;
                    }
                }
                
                // If we found valid odds, return them
                if ($oddsArray['home'] !== null || $oddsArray['draw'] !== null || $oddsArray['away'] !== null) {
                    return $oddsArray;
                }
            }
        }

        // Generate default random odds when no odds are available
        return $this->generateRandomOdds();
    }

    /**
     * Generate random realistic betting odds
     */
    private function generateRandomOdds()
    {
        // Generate realistic odds ranges
        $homeOdd = rand(150, 350) / 100; // 1.50 to 3.50
        $drawOdd = rand(280, 450) / 100; // 2.80 to 4.50
        $awayOdd = rand(150, 350) / 100; // 1.50 to 3.50
        
        // Ensure odds make sense (one team should be favorite)
        $scenarios = [
            // Home favorite
            ['home' => rand(150, 220) / 100, 'draw' => rand(320, 380) / 100, 'away' => rand(280, 450) / 100],
            // Away favorite  
            ['home' => rand(280, 450) / 100, 'draw' => rand(320, 380) / 100, 'away' => rand(150, 220) / 100],
            // Balanced match
            ['home' => rand(220, 280) / 100, 'draw' => rand(300, 350) / 100, 'away' => rand(220, 280) / 100],
        ];
        
        return $scenarios[array_rand($scenarios)];
    }

    /**
     * Calculate average goals from H2H data
     */
    private function calculateAverageGoals($h2h)
    {
        if (empty($h2h) || !is_array($h2h)) {
            return null;
        }

        $totalGoals = 0;
        $matchCount = 0;

        foreach ($h2h as $match) {
            if (isset($match['goals']['home']) && isset($match['goals']['away'])) {
                $totalGoals += $match['goals']['home'] + $match['goals']['away'];
                $matchCount++;
            }
        }

        return $matchCount > 0 ? round($totalGoals / $matchCount, 2) : null;
    }

    /**
     * Get prediction color based on confidence
     */
    private function getPredictionColor($prediction, $confidence)
    {
        if ($confidence >= 70) {
            return 'green'; // High confidence - green background
        } elseif ($confidence >= 50) {
            return 'white'; // Medium confidence - white background with border
        } else {
            return 'red'; // Low confidence - red border
        }
    }

    /**
     * Show match winner predictions for today's fixtures
     */
    public function showmatchwinner()
    {
        $today = Carbon::today()->toDateString();

        $fixtures = Fixture::whereDate('date', $today)
            ->where('has_odds', false)
            ->orderBy('date', 'asc')
            ->get();

        $matchWinnerData = $fixtures->map(function ($fixture) {
            $teams = is_array($fixture->teams) ? $fixture->teams : (json_decode($fixture->teams, true) ?? []);
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            // Get match winner prediction
            $matchWinnerPrediction = $this->predictMatchWinner($odds, $h2h, $fixture);
            
            // Get all available odds for match winner
            $matchWinnerOdds = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Match Winner');
            
            $availableOdds = [];
            if ($matchWinnerOdds && isset($matchWinnerOdds['values'])) {
                foreach ($matchWinnerOdds['values'] as $v) {
                    $availableOdds[$v['value']] = $v['odd'];
                }
            }
            
            // Calculate H2H statistics
            $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
            $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);
            $totalH2H = count($h2h);
            $draws = $totalH2H - $homeWins - $awayWins;

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->country_name,
                'league' => $fixture->league_name,
                'country_flag' => $fixture->country_flag,
                'league_id' => $fixture->league_id,
                'match_date' => $fixture->date,
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $teams['home']['logo'] ?? null,
                'away_logo' => $teams['away']['logo'] ?? null,
                'prediction' => $matchWinnerPrediction['prediction'],
                'confidence' => $matchWinnerPrediction['confidence'],
                'predicted_odd' => $matchWinnerPrediction['odd'],
                'available_odds' => $availableOdds,
                'h2h_stats' => [
                    'home_wins' => $homeWins,
                    'away_wins' => $awayWins,
                    'draws' => $draws,
                    'total_matches' => $totalH2H
                ],
                'status' => $fixture->status ?? 'Scheduled'
            ];
        });

        // Group by league for better organization
        $groupedData = $matchWinnerData->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('matchwinner', ['grouped' => $groupedData]);
    }

    /**
     * Show double chance predictions for today's fixtures
     */
    public function showdoublechance()
    {
        $today = Carbon::today()->toDateString();

        $fixtures = Fixture::whereDate('date', $today)
            ->where('has_odds', false)
            ->orderBy('date', 'asc')
            ->get();

        $doubleChanceData = $fixtures->map(function ($fixture) {
            $teams = is_array($fixture->teams) ? $fixture->teams : (json_decode($fixture->teams, true) ?? []);
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            $doubleChancePrediction = $this->predictDoubleChance($odds, $h2h, $fixture);
            
            $doubleChanceOdds = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Double Chance');
            
            $availableOdds = [];
            if ($doubleChanceOdds && isset($doubleChanceOdds['values'])) {
                foreach ($doubleChanceOdds['values'] as $v) {
                    $availableOdds[$v['value']] = $v['odd'];
                }
            }
            
            $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
            $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);
            $totalH2H = count($h2h);
            $draws = $totalH2H - $homeWins - $awayWins;

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->country_name,
                'league' => $fixture->league_name,
                'country_flag' => $fixture->country_flag,
                'match_date' => $fixture->date,
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $teams['home']['logo'] ?? null,
                'away_logo' => $teams['away']['logo'] ?? null,
                'prediction' => $doubleChancePrediction['prediction'],
                'confidence' => $doubleChancePrediction['confidence'],
                'predicted_odd' => $doubleChancePrediction['odd'],
                'available_odds' => $availableOdds,
                'h2h_stats' => [
                    'home_wins' => $homeWins,
                    'away_wins' => $awayWins,
                    'draws' => $draws,
                    'total_matches' => $totalH2H
                ]
            ];
        });

        $groupedData = $doubleChanceData->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('doublechance', ['grouped' => $groupedData]);
    }

    /**
     * Show both teams to score predictions for today's fixtures
     */
    public function showbothteamtoscore()
    {
        $today = Carbon::today()->toDateString();

        $fixtures = Fixture::whereDate('date', $today)
            ->where('has_odds', false)
            ->orderBy('date', 'asc')
            ->get();

        $bttsData = $fixtures->map(function ($fixture) {
            $teams = is_array($fixture->teams) ? $fixture->teams : (json_decode($fixture->teams, true) ?? []);
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            $bttsPrediction = $this->predictBothTeamsToScore($odds, $h2h, $stats);
            
            $bttsOdds = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Both Teams To Score');
            
            $availableOdds = [];
            if ($bttsOdds && isset($bttsOdds['values'])) {
                foreach ($bttsOdds['values'] as $v) {
                    $availableOdds[$v['value']] = $v['odd'];
                }
            }

            // Calculate BTTS statistics from H2H
            $bothScoredCount = 0;
            $totalGoalsScored = [];
            foreach ($h2h as $match) {
                $homeGoals = $match['goals']['home'] ?? 0;
                $awayGoals = $match['goals']['away'] ?? 0;
                $totalGoalsScored[] = $homeGoals + $awayGoals;
                
                if ($homeGoals > 0 && $awayGoals > 0) {
                    $bothScoredCount++;
                }
            }

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->country_name,
                'league' => $fixture->league_name,
                'country_flag' => $fixture->country_flag,
                'match_date' => $fixture->date,
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $teams['home']['logo'] ?? null,
                'away_logo' => $teams['away']['logo'] ?? null,
                'prediction' => $bttsPrediction['prediction'],
                'confidence' => $bttsPrediction['confidence'],
                'predicted_odd' => $bttsPrediction['odd'],
                'available_odds' => $availableOdds,
                'btts_stats' => [
                    'both_scored_count' => $bothScoredCount,
                    'total_matches' => count($h2h),
                    'btts_percentage' => count($h2h) > 0 ? round(($bothScoredCount / count($h2h)) * 100, 1) : 0,
                    'avg_goals' => count($totalGoalsScored) > 0 ? round(array_sum($totalGoalsScored) / count($totalGoalsScored), 1) : 0
                ]
            ];
        });

        $groupedData = $bttsData->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('bothteamstoscore', ['grouped' => $groupedData]);
    }

    /**
     * Show over/under 2.5 goals predictions for today's fixtures
     */
    public function showoverunder25()
    {
        $today = Carbon::today()->toDateString();

        $fixtures = Fixture::whereDate('date', $today)
            ->where('has_odds', false)
            ->orderBy('date', 'asc')
            ->get();

        $ou25Data = $fixtures->map(function ($fixture) {
            $teams = is_array($fixture->teams) ? $fixture->teams : (json_decode($fixture->teams, true) ?? []);
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            $ou25Prediction = $this->predictOverUnder25($odds, $h2h, $stats);
            
            $ou25Odds = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Goals Over/Under');
            
            $availableOdds = [];
            if ($ou25Odds && isset($ou25Odds['values'])) {
                foreach ($ou25Odds['values'] as $v) {
                    if (strpos($v['value'], '2.5') !== false) {
                        $availableOdds[$v['value']] = $v['odd'];
                    }
                }
            }

            // Calculate goal statistics from H2H
            $overCount = 0;
            $underCount = 0;
            $totalGoals = [];
            foreach ($h2h as $match) {
                $homeGoals = $match['goals']['home'] ?? 0;
                $awayGoals = $match['goals']['away'] ?? 0;
                $matchGoals = $homeGoals + $awayGoals;
                $totalGoals[] = $matchGoals;
                
                if ($matchGoals > 2.5) {
                    $overCount++;
                } else {
                    $underCount++;
                }
            }

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->country_name,
                'league' => $fixture->league_name,
                'country_flag' => $fixture->country_flag,
                'match_date' => $fixture->date,
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $teams['home']['logo'] ?? null,
                'away_logo' => $teams['away']['logo'] ?? null,
                'prediction' => $ou25Prediction['prediction'],
                'confidence' => $ou25Prediction['confidence'],
                'predicted_odd' => $ou25Prediction['odd'],
                'available_odds' => $availableOdds,
                'goals_stats' => [
                    'over_count' => $overCount,
                    'under_count' => $underCount,
                    'total_matches' => count($h2h),
                    'over_percentage' => count($h2h) > 0 ? round(($overCount / count($h2h)) * 100, 1) : 0,
                    'avg_goals' => count($totalGoals) > 0 ? round(array_sum($totalGoals) / count($totalGoals), 1) : 0
                ]
            ];
        });

        $groupedData = $ou25Data->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('overunder25', ['grouped' => $groupedData]);
    }

    /**
     * Show halftime/fulltime predictions for today's fixtures
     */
    public function showhtft()
    {
        $today = Carbon::today()->toDateString();

        $fixtures = Fixture::whereDate('date', $today)
            ->where('has_odds', false)
            ->orderBy('date', 'asc')
            ->get();

        $htftData = $fixtures->map(function ($fixture) {
            $teams = is_array($fixture->teams) ? $fixture->teams : (json_decode($fixture->teams, true) ?? []);
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            $htftPrediction = $this->predictHalftimeFulltime($odds, $h2h, $fixture);
            
            $htftOdds = collect($odds)
                ->pluck('bookmakers')
                ->flatten(1)
                ->pluck('bets')
                ->flatten(1)
                ->firstWhere('name', 'Halftime/Fulltime');
            
            $availableOdds = [];
            if ($htftOdds && isset($htftOdds['values'])) {
                foreach ($htftOdds['values'] as $v) {
                    $availableOdds[$v['value']] = $v['odd'];
                }
            }

            $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
            $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);
            $totalH2H = count($h2h);
            $draws = $totalH2H - $homeWins - $awayWins;

            return [
                'fixture_id' => $fixture->fixture_id,
                'country' => $fixture->country_name,
                'league' => $fixture->league_name,
                'country_flag' => $fixture->country_flag,
                'match_date' => $fixture->date,
                'home_team' => $fixture->home_team_name ?? 'N/A',
                'away_team' => $fixture->away_team_name ?? 'N/A',
                'home_logo' => $teams['home']['logo'] ?? null,
                'away_logo' => $teams['away']['logo'] ?? null,
                'prediction' => $htftPrediction['prediction'],
                'confidence' => $htftPrediction['confidence'],
                'predicted_odd' => $htftPrediction['odd'],
                'available_odds' => $availableOdds,
                'h2h_stats' => [
                    'home_wins' => $homeWins,
                    'away_wins' => $awayWins,
                    'draws' => $draws,
                    'total_matches' => $totalH2H
                ]
            ];
        });

        $groupedData = $htftData->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('htft', ['grouped' => $groupedData]);
    }

    /**
     * Get comprehensive predictions for all betting markets
     */
    public function getAllMarketPredictions($fixture, $odds, $h2h, $stats)
    {
        return [
            'match_winner' => $this->predictMatchWinner($odds, $h2h, $fixture),
            'double_chance' => $this->predictDoubleChance($odds, $h2h, $fixture),
            'halftime_fulltime' => $this->predictHalftimeFulltime($odds, $h2h, $fixture),
            'both_teams_score' => $this->predictBothTeamsToScore($odds, $h2h, $stats),
            'over_under_25' => $this->predictOverUnder25($odds, $h2h, $stats)
        ];
    }

    /**
     * Get prediction for a specific market
     */
    public function getMarketPrediction($market, $fixture, $odds, $h2h, $stats)
    {
        switch (strtolower($market)) {
            case 'match_winner':
            case 'matchwinner':
                return $this->predictMatchWinner($odds, $h2h, $fixture);
            
            case 'double_chance':
            case 'doublechance':
                return $this->predictDoubleChance($odds, $h2h, $fixture);
            
            case 'halftime_fulltime':
            case 'htft':
                return $this->predictHalftimeFulltime($odds, $h2h, $fixture);
            
            case 'both_teams_score':
            case 'btts':
                return $this->predictBothTeamsToScore($odds, $h2h, $stats);
            
            case 'over_under_25':
            case 'ou25':
                return $this->predictOverUnder25($odds, $h2h, $stats);
            
            default:
                return $this->predictMatchWinner($odds, $h2h, $fixture);
        }
    }

    /**
     * Predict Match Winner market
     */
    private function predictMatchWinner($odds, $h2h, $fixture)
    {
        $matchWinner = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->firstWhere('name', 'Match Winner');

        if (!$matchWinner || !isset($matchWinner['values'])) {
            return ['prediction' => 'Draw', 'confidence' => 50, 'odd' => null];
        }

        $weighted = [];
        foreach ($matchWinner['values'] as $v) {
            $odd = (float) $v['odd'];
            $weighted[$v['value']] = 1 / $odd;
        }

        // Apply H2H weighting
        $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
        $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);

        if ($homeWins > $awayWins && isset($weighted['Home'])) {
            $weighted['Home'] *= 1.2;
        } elseif ($awayWins > $homeWins && isset($weighted['Away'])) {
            $weighted['Away'] *= 1.2;
        }

        $prediction = $this->weightedRandom($weighted);
        $oddValue = null;

        foreach ($matchWinner['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }

        return [
            'prediction' => $prediction,
            'confidence' => rand(65, 90),
            'odd' => $oddValue
        ];
    }

    /**
     * Predict Double Chance market (1X, 12, X2)
     */
    private function predictDoubleChance($odds, $h2h, $fixture)
    {
        $doubleChance = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->firstWhere('name', 'Double Chance');

        if (!$doubleChance || !isset($doubleChance['values'])) {
            return ['prediction' => '1X', 'confidence' => 60, 'odd' => null];
        }

        $weighted = [];
        foreach ($doubleChance['values'] as $v) {
            $odd = (float) $v['odd'];
            $weighted[$v['value']] = 1 / $odd;
        }

        // Apply team form analysis
        $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
        $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);

        if ($homeWins > $awayWins) {
            if (isset($weighted['1X'])) $weighted['1X'] *= 1.15;
            if (isset($weighted['12'])) $weighted['12'] *= 1.1;
        } elseif ($awayWins > $homeWins) {
            if (isset($weighted['X2'])) $weighted['X2'] *= 1.15;
            if (isset($weighted['12'])) $weighted['12'] *= 1.1;
        }

        $prediction = $this->weightedRandom($weighted);
        $oddValue = null;

        foreach ($doubleChance['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }

        return [
            'prediction' => $prediction,
            'confidence' => rand(70, 85),
            'odd' => $oddValue
        ];
    }

    /**
     * Predict Halftime/Fulltime market
     */
    private function predictHalftimeFulltime($odds, $h2h, $fixture)
    {
        $htft = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->firstWhere('name', 'Halftime/Fulltime');

        if (!$htft || !isset($htft['values'])) {
            return ['prediction' => '1/1', 'confidence' => 45, 'odd' => null];
        }

        $weighted = [];
        foreach ($htft['values'] as $v) {
            $odd = (float) $v['odd'];
            $weighted[$v['value']] = 1 / $odd;
        }

        // Favor consistent results (1/1, X/X, 2/2)
        $consistentResults = ['1/1', 'X/X', '2/2'];
        foreach ($consistentResults as $result) {
            if (isset($weighted[$result])) {
                $weighted[$result] *= 1.3;
            }
        }

        $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id);
        $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id);

        if ($homeWins > $awayWins) {
            $homeResults = ['1/1', '1/X', '1/2'];
            foreach ($homeResults as $result) {
                if (isset($weighted[$result])) {
                    $weighted[$result] *= 1.2;
                }
            }
        } elseif ($awayWins > $homeWins) {
            $awayResults = ['2/2', '2/X', '2/1'];
            foreach ($awayResults as $result) {
                if (isset($weighted[$result])) {
                    $weighted[$result] *= 1.2;
                }
            }
        }

        $prediction = $this->weightedRandom($weighted);
        $oddValue = null;

        foreach ($htft['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }

        return [
            'prediction' => $prediction,
            'confidence' => rand(40, 70),
            'odd' => $oddValue
        ];
    }

    /**
     * Predict Both Teams to Score market
     */
    private function predictBothTeamsToScore($odds, $h2h, $stats)
    {
        $btts = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->firstWhere('name', 'Both Teams To Score');

        if (!$btts || !isset($btts['values'])) {
            return ['prediction' => 'Yes', 'confidence' => 55, 'odd' => null];
        }

        $weighted = [];
        foreach ($btts['values'] as $v) {
            $odd = (float) $v['odd'];
            $weighted[$v['value']] = 1 / $odd;
        }

        // Analyze H2H scoring patterns
        $bothScoredCount = 0;
        $totalMatches = count($h2h);

        foreach ($h2h as $match) {
            $homeGoals = $match['goals']['home'] ?? 0;
            $awayGoals = $match['goals']['away'] ?? 0;
            
            if ($homeGoals > 0 && $awayGoals > 0) {
                $bothScoredCount++;
            }
        }

        if ($totalMatches > 0) {
            $bothScoreRate = $bothScoredCount / $totalMatches;
            
            if ($bothScoreRate > 0.6 && isset($weighted['Yes'])) {
                $weighted['Yes'] *= 1.4;
            } elseif ($bothScoreRate < 0.3 && isset($weighted['No'])) {
                $weighted['No'] *= 1.4;
            }
        }

        $prediction = $this->weightedRandom($weighted);
        $oddValue = null;

        foreach ($btts['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }

        return [
            'prediction' => $prediction,
            'confidence' => rand(60, 80),
            'odd' => $oddValue
        ];
    }

    /**
     * Predict Over/Under 2.5 Goals market
     */
    private function predictOverUnder25($odds, $h2h, $stats)
    {
        $ou25 = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->firstWhere('name', 'Goals Over/Under');

        if (!$ou25 || !isset($ou25['values'])) {
            return ['prediction' => 'Over 2.5', 'confidence' => 55, 'odd' => null];
        }

        $weighted = [];
        foreach ($ou25['values'] as $v) {
            // Look for 2.5 goals line specifically
            if (strpos($v['value'], '2.5') !== false) {
                $odd = (float) $v['odd'];
                $weighted[$v['value']] = 1 / $odd;
            }
        }

        if (empty($weighted)) {
            return ['prediction' => 'Over 2.5', 'confidence' => 50, 'odd' => null];
        }

        // Analyze H2H goal patterns
        $overCount = 0;
        $totalMatches = count($h2h);

        foreach ($h2h as $match) {
            $homeGoals = $match['goals']['home'] ?? 0;
            $awayGoals = $match['goals']['away'] ?? 0;
            $totalGoals = $homeGoals + $awayGoals;
            
            if ($totalGoals > 2.5) {
                $overCount++;
            }
        }

        if ($totalMatches > 0) {
            $overRate = $overCount / $totalMatches;
            
            if ($overRate > 0.6) {
                foreach ($weighted as $key => $weight) {
                    if (strpos($key, 'Over') !== false) {
                        $weighted[$key] *= 1.3;
                    }
                }
            } elseif ($overRate < 0.4) {
                foreach ($weighted as $key => $weight) {
                    if (strpos($key, 'Under') !== false) {
                        $weighted[$key] *= 1.3;
                    }
                }
            }
        }

        $prediction = $this->weightedRandom($weighted);
        $oddValue = null;

        foreach ($ou25['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }

        return [
            'prediction' => $prediction,
            'confidence' => rand(55, 75),
            'odd' => $oddValue
        ];
    }
}
