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
        return $this->getPredictionsForDate(Carbon::today()->toDateString(), 'Accurate Football Predictions', false, 'home', false, [
            'title' => 'Accurate Football Predictions | Amazingstakes',
            'description' => 'Get daily winning football tips, 3 sure draws, and sure straight wins from Amazingstakes. Trusted in Nigeria, Kenya, Tanzania, Uganda, Ghana, Vietnam & USA.',
            'keywords' => 'Best prediction site, Sure Straight Wins Today, delivers winning tips, everyday winning tips, Accurate football prediction site, football tips, sure predictions, sites that predict football matches correctly, football prediction, unbeatable soccer predictions, successful soccer prediction',
            'canonical' => url('/')
        ]);
    }

    public function yesterday()
    {
        return $this->getPredictionsForDate(Carbon::yesterday()->toDateString(), 'Yesterday Football Predictions', false, 'yesterday', false, [
            'title' => 'Yesterday Football Predictions | Amazingstakes',
            'description' => 'See yesterday\'s football predictions and results. Check how our tips performed.',
            'keywords' => 'yesterday football predictions, yesterday soccer results, football tips yesterday, soccer predictions yesterday',
            'canonical' => route('yesterday')
        ]);
    }

    public function tomorrow()
    {
        return $this->getPredictionsForDate(Carbon::tomorrow()->toDateString(), 'Football Tips Tomorrow', false, 'tomorrow', false, [
            'title' => 'Football Tips Tomorrow | Amazingstakes',
            'description' => 'Get early football tips for tomorrow. Prepare your bets with our accurate predictions for tomorrow\'s matches.',
            'keywords' => 'football tips tomorrow, tomorrow predictions, soccer predictions tomorrow, upcoming football tips',
            'canonical' => route('tomorrow')
        ]);
    }

    public function weekend()
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY)->toDateString();
        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();
        
        // If today is Saturday, use today and tomorrow (Sunday)
        if (Carbon::now()->isSaturday()) {
            $saturday = Carbon::today()->toDateString();
            $sunday = Carbon::tomorrow()->toDateString();
        }
        // If today is Sunday, use today only (or maybe next weekend? usually users want upcoming games)
        // Let's assume if it's Sunday, we show today's games as "Weekend" or maybe next weekend.
        // Standard practice: "Weekend" usually means upcoming Sat/Sun.
        // If today is Sunday, "Weekend" might refer to *this* weekend (so just today left) or *next* weekend.
        // Let's stick to: Weekend = This coming Saturday and Sunday.
        // If today is Sunday, let's show today.
        if (Carbon::now()->isSunday()) {
             $saturday = Carbon::today()->toDateString(); // It's Sunday, so start today
             $sunday = Carbon::today()->toDateString();
        }

        return $this->getPredictionsForDate([$saturday, $sunday], 'Weekend Football Predictions', false, 'weekend', false, [
            'title' => 'Weekend Football Predictions | Amazingstakes',
            'description' => 'Get the best football predictions for this weekend. Saturday and Sunday tips for major leagues.',
            'keywords' => 'weekend football predictions, weekend soccer tips, saturday football predictions, sunday football predictions',
            'canonical' => route('weekend')
        ]);
    }

    public function mustWin()
    {
        return $this->getPredictionsForDate(Carbon::today()->toDateString(), 'Must Win Teams Today', true, 'must_win', false, [
            'title' => 'Must Win Teams Today | Amazingstakes',
            'description' => 'Discover the teams that must win today. High confidence predictions for teams fighting for points.',
            'keywords' => 'must win teams today, sure wins today, football teams to win, banker tips',
            'canonical' => route('must_win')
        ]);
    }

    public function upcoming()
    {
        // Get next 4 days starting from tomorrow
        $dates = [];
        for ($i = 1; $i <= 4; $i++) {
            $dates[] = Carbon::today()->addDays($i)->toDateString();
        }
        
        return $this->getPredictionsForDate($dates, 'Upcoming Football Predictions', false, 'upcoming', true, [
            'title' => 'Upcoming Football Predictions | Amazingstakes',
            'description' => 'Check out upcoming football predictions for the next few days. Plan your bets ahead with our expert analysis.',
            'keywords' => 'upcoming football predictions, future soccer matches, football tips next 3 days, upcoming fixtures predictions',
            'canonical' => route('upcoming')
        ]);
    }

    public function tips180()
    {
        return view('tips180', [
            'pageTitle' => '180 Predictions Today',
            'seoTitle' => '180 Predictions Today | Amazingstakes',
            'seoDescription' => 'Get our exclusive 180 predictions today. High value tips for serious bettors.',
            'seoKeywords' => '180 predictions, 180 tips, high value football tips, expert soccer predictions',
            'canonicalUrl' => route('tips180')
        ]);
    }

    public function victorPredict()
    {
        return view('victor', [
            'pageTitle' => 'Top Predictions (Top Picks)',
            'seoTitle' => 'Top Predictions (Top Picks) | Amazingstakes',
            'seoDescription' => 'Our top picks and best predictions for today. Hand-picked selections by our experts.',
            'seoKeywords' => 'top predictions, top picks, best football tips, expert picks today',
            'canonicalUrl' => route('victor_predict')
        ]);
    }

    public function jackpot()
    {
        return view('jackpot', [
            'pageTitle' => 'Jackpot Predictions',
            'seoTitle' => 'Jackpot Predictions | Amazingstakes',
            'seoDescription' => 'Win big with our jackpot predictions. Analysis for major jackpot pools.',
            'seoKeywords' => 'jackpot predictions, mega jackpot tips, football jackpot analysis',
            'canonicalUrl' => route('jackpot')
        ]);
    }

    public function trends()
    {
        return view('trends', [
            'pageTitle' => 'Top Trends',
            'seoTitle' => 'Top Trends | Amazingstakes',
            'seoDescription' => 'Follow the latest football trends. Teams on winning streaks, goal scoring trends, and more.',
            'seoKeywords' => 'football trends, soccer betting trends, team streaks, form guide',
            'canonicalUrl' => route('trends')
        ]);
    }

    private function getPredictionsForDate($date, $titlePrefix, $mustWinOnly = false, $viewName = 'home', $mixMarkets = false, $seoData = [])
    {
        // Default SEO data
        $seoTitle = $seoData['title'] ?? $titlePrefix . ' | Amazingstakes';
        $seoDescription = $seoData['description'] ?? 'Get daily winning football tips, 3 sure draws, and sure straight wins from Amazingstakes. Trusted in Nigeria, Kenya, Tanzania, Uganda, Ghana, Vietnam & USA.';
        $seoKeywords = $seoData['keywords'] ?? 'Best prediction site, Sure Straight Wins Today, delivers winning tips, everyday winning tips, Accurate football prediction site, football tips, sure predictions, sites that predict football matches correctly, football prediction, unbeatable soccer predictions, successful soccer prediction';
        $canonicalUrl = $seoData['canonical'] ?? url()->current();
        // Fetch fixtures with their relationships
        $query = Fixture::with(['league', 'odds'])
            ->orderBy('date', 'asc');

        if (is_array($date)) {
            $query->whereBetween('date', $date);
        } else {
            $query->whereDate('date', $date);
        }

        $fixtures = $query->get();

        // Transform fixtures into structured data
        $data = $fixtures->map(function ($fixture) use ($mixMarkets) {
            // Parse JSON fields if they're stored as strings
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            // Get prediction for this fixture
            if ($mixMarkets) {
                $predictionOutcome = $this->getBestPrediction($fixture, $odds, $h2h, $stats);
            } else {
                $predictionOutcome = $this->predictFixture($fixture, $odds, $h2h, $stats);
            }

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
                'prediction_color' => $this->getPredictionColor(
                        $predictionOutcome['prediction'], 
                        $predictionOutcome['confidence'], 
                        $fixture->goals_home, 
                        $fixture->goals_away
                    ),
                'market_name' => $predictionOutcome['market_name'] ?? 'Match Winner',
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

        if ($mustWinOnly) {
            $data = $data->filter(function ($item) {
                // Must be Home (1) or Away (2) win, and confidence >= 75
                return in_array($item['prediction'], ['1', '2']) && $item['confidence'] >= 75;
            })->sortByDesc('confidence');
        }

        $today = Carbon::today()->toDateString();

        // Fetch today's fixtures with their relationships
        $fixtures = Fixture::with(['league', 'odds'])
            ->whereDate('date', $today)
            ->orderBy('date', 'asc')
            ->get();

        // Transform fixtures into structured data for double chance predictions
        $data = $fixtures->map(function ($fixture) {
            // Parse JSON fields if they're stored as strings
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            // Get double chance specific prediction
            $doubleChancePrediction = $this->predictDoubleChance($odds, $h2h, $fixture);

            // Get double chance odds instead of match winner odds
            $doubleChanceOdds = $this->getDoubleChanceOdds($fixture, $odds);

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
                'prediction' => $doubleChancePrediction['prediction'] ?? 'N/A',
                'confidence' => $doubleChancePrediction['confidence'] ?? 0,
                'prediction_color' => $this->getPredictionColor(
                    $doubleChancePrediction['prediction'] ?? 'N/A', 
                    $doubleChancePrediction['confidence'] ?? 0,
                    $fixture->goals_home ?? null,
                    $fixture->goals_away ?? null
                ),
                                'odds' => $doubleChanceOdds,
                'avg_goals' => $avgGoals,
                'status' => $fixture->status_long ?? 'Scheduled',
                'status_short' => $fixture->status_short ?? 'NS',
                'home_score' => $fixture->goals_home ?? null,
                'away_score' => $fixture->goals_away ?? null,
                'halftime_home' => $fixture->halftime_home ?? null,
                'halftime_away' => $fixture->halftime_away ?? null,
                'elapsed' => $fixture->elapsed ?? null,
                'venue' => $fixture->venue_name ?? null,
                'is_finished' => in_array($fixture->status_short ?? 'NS', ['FT', 'AET', 'PEN']),
                'has_started' => !in_array($fixture->status_short ?? 'NS', ['NS', 'TBD', 'CANC', 'PST']),
            ];
        });

        // Group by Country - League for organized display
        $grouped = $data->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });

        return view('doublechance', ['grouped' => $grouped]);
    }

    private function getDoubleChanceOdds($fixture, $odds)
    {
        // Try to get double chance odds from odds data
        if (!empty($odds) && is_array($odds)) {
            foreach ($odds as $oddsEntry) {
                if (isset($oddsEntry['bookmakers']) && is_array($oddsEntry['bookmakers'])) {
                    foreach ($oddsEntry['bookmakers'] as $bookmaker) {
                        if (isset($bookmaker['bets']) && is_array($bookmaker['bets'])) {
                            foreach ($bookmaker['bets'] as $bet) {
                                if (isset($bet['name']) && $bet['name'] === 'Double Chance' && isset($bet['values'])) {
                                    $oddsArray = ['home' => null, 'draw' => null, 'away' => null];
                                    foreach ($bet['values'] as $value) {
                                        switch ($value['value']) {
                                            case '1X': // Home or Draw
                                                $oddsArray['home'] = (float) $value['odd'];
                                                break;
                                            case 'X2': // Draw or Away
                                                $oddsArray['draw'] = (float) $value['odd'];
                                                break;
                                            case '12': // Home or Away
                                                $oddsArray['away'] = (float) $value['odd'];
                                                break;
                                        }
                                    }
                                    
                                    if ($oddsArray['home'] !== null || $oddsArray['draw'] !== null || $oddsArray['away'] !== null) {
                                        return $oddsArray;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Generate default double chance odds if none found
        return $this->generateRandomDoubleChanceOdds();
    }
    public function htft()
    {
        $today = Carbon::today()->toDateString();
    
        // Fetch today's fixtures with their relationships
        $fixtures = Fixture::with(['league', 'odds'])
            ->whereDate('date', $today)
            ->orderBy('date', 'asc')
            ->get();
    
        // Transform fixtures into structured data for halftime/fulltime predictions
        $data = $fixtures->map(function ($fixture) {
            // Parse JSON fields if they're stored as strings
            $odds = is_array($fixture->odds) ? $fixture->odds : (json_decode($fixture->odds, true) ?? []);
            $h2h = is_array($fixture->head2head) ? $fixture->head2head : (json_decode($fixture->head2head, true) ?? []);
            $stats = is_array($fixture->statistics) ? $fixture->statistics : (json_decode($fixture->statistics, true) ?? []);
            
            // Get halftime/fulltime specific prediction
            $htftPrediction = $this->predictHalftimeFulltime($odds, $h2h, $fixture);
    
            // Get halftime/fulltime odds instead of match winner odds
            $htftOdds = $this->getHalftimeFulltimeOdds($fixture, $odds);
    
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
                'prediction' => $htftPrediction['prediction'] ?? 'N/A',
                'confidence' => $htftPrediction['confidence'] ?? 0,
                'prediction_color' => $this->getPredictionColor(
                    $htftPrediction['prediction'] ?? 'N/A', 
                    $htftPrediction['confidence'] ?? 0,
                    $fixture->goals_home ?? null,
                    $fixture->goals_away ?? null
                ),
                'odds' => $htftOdds,
                'avg_goals' => $avgGoals,
                'status' => $fixture->status_long ?? 'Scheduled',
                'status_short' => $fixture->status_short ?? 'NS',
                'home_score' => $fixture->goals_home ?? null,
                'away_score' => $fixture->goals_away ?? null,
                'halftime_home' => $fixture->halftime_home ?? null,
                'halftime_away' => $fixture->halftime_away ?? null,
                'elapsed' => $fixture->elapsed ?? null,
                'venue' => $fixture->venue_name ?? null,
                'is_finished' => in_array($fixture->status_short ?? 'NS', ['FT', 'AET', 'PEN']),
                'has_started' => !in_array($fixture->status_short ?? 'NS', ['NS', 'TBD', 'CANC', 'PST']),
            ];
        });
    
        // Group by Country - League for organized display
        $grouped = $data->groupBy(function ($item) {
            return $item['country'] . ' - ' . $item['league'];
        });
    
        return view('htft', ['grouped' => $grouped]);
    }
    
    // Add this new method to get halftime/fulltime odds specifically
    private function getHalftimeFulltimeOdds($fixture, $odds)
    {
        // Try to get halftime/fulltime odds from odds data
        if (!empty($odds) && is_array($odds)) {
            foreach ($odds as $oddsEntry) {
                if (isset($oddsEntry['bookmakers']) && is_array($oddsEntry['bookmakers'])) {
                    foreach ($oddsEntry['bookmakers'] as $bookmaker) {
                        if (isset($bookmaker['bets']) && is_array($bookmaker['bets'])) {
                            foreach ($bookmaker['bets'] as $bet) {
                                if (isset($bet['name']) && $bet['name'] === 'HT/FT Double"' && isset($bet['values'])) {
                                    $oddsArray = ['home' => null, 'draw' => null, 'away' => null];
                                    foreach ($bet['values'] as $value) {
                                        switch ($value['value']) {
                                            case 'Home/Home': // Home/Home
                                                $oddsArray['home'] = (float) $value['odd'];
                                                break;
                                            case 'Draw/Draw': // Draw/Draw
                                                $oddsArray['draw'] = (float) $value['odd'];
                                                break;
                                            case 'Away/Away': // Away/Away
                                                $oddsArray['away'] = (float) $value['odd'];
                                                break;
                                            case 'Home/Draw': // Home/Draw
                                                $oddsArray['home'] = (float) $value['odd'];
                                                break;
                                            case 'Draw/Away': // Draw/Away
                                                $oddsArray['draw'] = (float) $value['odd'];
                                                break;
                                            case 'Away/Home': // Away/Home
                                                $oddsArray['away'] = (float) $value['odd'];
                                                break;


                                        }
                                    }
                                    
                                    if ($oddsArray['home'] !== null || $oddsArray['draw'] !== null || $oddsArray['away'] !== null) {
                                        return $oddsArray;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    
        // Generate default halftime/fulltime odds if none found
        return $this->generateRandomHtftOdds();
    }
    
    // Add this method to generate random halftime/fulltime odds
    private function generateRandomHtftOdds()
    {
        // HT/FT odds are typically higher than regular match winner odds
        return [
            'home' => rand(300, 800) / 100, // 1/1 odds (3.00 to 8.00)
            'draw' => rand(600, 1500) / 100, // X/X odds (6.00 to 15.00)
            'away' => rand(300, 800) / 100, // 2/2 odds (3.00 to 8.00)
        ];
    }
    private function generateRandomDoubleChanceOdds()
    {
        // Double chance odds are typically lower than match winner odds
        return [
            'home' => rand(110, 150) / 100, // 1X odds (1.10 to 1.50)
            'draw' => rand(110, 150) / 100, // X2 odds (1.10 to 1.50)
            'away' => rand(110, 150) / 100, // 12 odds (1.10 to 1.50)
        ];
    }

    private function getBestPrediction($fixture, $odds, $h2h, $stats)
    {
        $predictions = [];

        // 1. Match Winner
        $matchWinner = $this->predictFixture($fixture, $odds, $h2h, $stats);
        $predictions[] = [
            'market_name' => 'Match Winner',
            'prediction' => $matchWinner['prediction'],
            'confidence' => $matchWinner['confidence'],
            'odd' => $matchWinner['odd']
        ];

        // 2. Double Chance (Simulated logic based on Match Winner for now to save complexity)
        // If Match Winner confidence is low (< 60), Double Chance is usually safer/higher confidence
        $dcPrediction = '1X';
        $dcConfidence = $matchWinner['confidence'] + 15; // Boost confidence
        if ($matchWinner['prediction'] === '2') {
            $dcPrediction = 'X2';
        } elseif ($matchWinner['prediction'] === 'X') {
            $dcPrediction = '1X'; // or X2, let's default to Home/Draw
        }
        
        $predictions[] = [
            'market_name' => 'Double Chance',
            'prediction' => $dcPrediction,
            'confidence' => min($dcConfidence, 99), // Cap at 99
            'odd' => null // We'd need to fetch specific odds
        ];

        // 3. Over/Under 2.5
        $avgGoals = $this->calculateAverageGoals($h2h);
        if ($avgGoals !== null) {
            $ouPrediction = $avgGoals > 2.5 ? 'Over 2.5' : 'Under 2.5';
            // Simple confidence metric based on how far from 2.5
            $diff = abs($avgGoals - 2.5);
            $ouConfidence = 50 + ($diff * 20); // e.g. 3.0 avg -> 0.5 diff -> 60% confidence
            
            $predictions[] = [
                'market_name' => 'Over/Under 2.5',
                'prediction' => $ouPrediction,
                'confidence' => min($ouConfidence, 95),
                'odd' => null
            ];
        }

        // 4. BTTS
        // Simple logic: if avg goals > 2.5, likely BTTS Yes
        if ($avgGoals !== null) {
            $bttsPrediction = $avgGoals > 2.5 ? 'Yes' : 'No';
            $bttsConfidence = 50 + (abs($avgGoals - 2.5) * 15);
            
            $predictions[] = [
                'market_name' => 'Both Teams To Score',
                'prediction' => $bttsPrediction,
                'confidence' => min($bttsConfidence, 90),
                'odd' => null
            ];
        }

        // Sort by confidence descending
        usort($predictions, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        // Return the best one
        return $predictions[0];
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
  /**
 * Get prediction color based on confidence
 */
private function getPredictionColor($prediction, $confidence, $homeScore = null, $awayScore = null, $halftimeHome = null, $halftimeAway = null)
{
    // If no scores available (match not started/finished), use orange as default
    if ($homeScore === null || $awayScore === null) {
        return 'orange'; // Default color for unfinished matches
    }
    
    // Convert scores to integers for comparison
    $homeScore = (int) $homeScore;
    $awayScore = (int) $awayScore;
    $halftimeHome = (int) ($halftimeHome ?? 0);
    $halftimeAway = (int) ($halftimeAway ?? 0);
    
    // Determine actual match outcome
    $actualOutcome = '';
    if ($homeScore > $awayScore) {
        $actualOutcome = '1'; // Home win
    } elseif ($homeScore < $awayScore) {
        $actualOutcome = '2'; // Away win
    } else {
        $actualOutcome = 'X'; // Draw
    }
    
    // Check if prediction matches actual outcome
    $predictionCorrect = false;
    
    switch (strtoupper($prediction)) {
        case '1':
        case 'HOME':
            $predictionCorrect = ($actualOutcome === '1');
            break;
            
        case '2':
        case 'AWAY':
            $predictionCorrect = ($actualOutcome === '2');
            break;
            
        case 'X':
        case 'DRAW':
            $predictionCorrect = ($actualOutcome === 'X');
            break;
            
        // Double chance predictions
        case '1X':
            $predictionCorrect = ($actualOutcome === '1' || $actualOutcome === 'X');
            break;
            
        case 'X2':
            $predictionCorrect = ($actualOutcome === 'X' || $actualOutcome === '2');
            break;
            
        case '12':
            $predictionCorrect = ($actualOutcome === '1' || $actualOutcome === '2');
            break;
            
        // BTTS predictions
        case 'YES':
        case 'BOTH TEAMS TO SCORE':
            $predictionCorrect = ($homeScore > 0 && $awayScore > 0);
            break;
            
        case 'NO':
        case 'NO BTTS':
            $predictionCorrect = ($homeScore === 0 || $awayScore === 0);
            break;
            
        // Over/Under 2.5 predictions
        case 'OVER 2.5':
        case 'OVER':
            $predictionCorrect = (($homeScore + $awayScore) > 2.5);
            break;
            
        case 'UNDER 2.5':
        case 'UNDER':
            $predictionCorrect = (($homeScore + $awayScore) < 2.5);
            break;
            
        // Halftime/Fulltime predictions
        case '1/1':
            $predictionCorrect = ($actualOutcome === '1' && $halftimeHome > $halftimeAway);
            break;
            
        case 'X/X':
            $predictionCorrect = ($actualOutcome === 'X' && $halftimeHome == $halftimeAway);
            break;
            
        case '2/2':
            $predictionCorrect = ($actualOutcome === '2' && $halftimeAway > $halftimeHome);
            break;
            
        case '1/X':
            $predictionCorrect = ($actualOutcome === 'X' && $halftimeHome > $halftimeAway);
            break;
            
        case 'X/1':
            $predictionCorrect = ($actualOutcome === '1' && $halftimeHome == $halftimeAway);
            break;
            
        case '1/2':
            $predictionCorrect = ($actualOutcome === '2' && $halftimeHome > $halftimeAway);
            break;
            
        case '2/1':
            $predictionCorrect = ($actualOutcome === '1' && $halftimeAway > $halftimeHome);
            break;
            
        case 'X/2':
            $predictionCorrect = ($actualOutcome === '2' && $halftimeHome == $halftimeAway);
            break;
            
        case '2/X':
            $predictionCorrect = ($actualOutcome === 'X' && $halftimeAway > $halftimeHome);
            break;
            
        default:
            // For other prediction types, fall back to confidence-based coloring
            return $this->getConfidenceBasedColor($confidence);
    }
    
    // Return color based on prediction accuracy
    return $predictionCorrect ? 'green' : 'red';
}
    
    /**
     * Fallback method for confidence-based coloring
     */
    private function getConfidenceBasedColor($confidence)
    {
        if ($confidence >= 70) {
            return 'yellow'; // High confidence - yellow for unverified predictions
        } elseif ($confidence >= 50) {
            return 'white'; // Medium confidence - white background
        } else {
            return 'orange'; // Low confidence - orange
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
        // Generate H2H data if missing
        if (empty($h2h) || !is_array($h2h)) {
            $h2h = $this->generateRealisticH2H($fixture);
        }
    
        // Try to find HT/FT odds from various possible names
        $htft = collect($odds)
            ->pluck('bookmakers')
            ->flatten(1)
            ->pluck('bets')
            ->flatten(1)
            ->first(function($bet) {
                $name = strtolower($bet['name'] ?? '');
                return in_array($name, ['ht/ft double', 'halftime/fulltime', 'half time/full time', 'ht ft']);
            });
    
        // Generate HT/FT odds if not found
        if (!$htft || !isset($htft['values'])) {
            $htft = $this->generateRealisticHtftOdds();
        }
    
        $weighted = [];
        foreach ($htft['values'] as $v) {
            $odd = (float) $v['odd'];
            if ($odd > 0) { // Ensure valid odds
                $weighted[$v['value']] = 1 / $odd;
            }
        }
    
        // If no weighted data, create default weights
        if (empty($weighted)) {
            $weighted = [
                '1/1' => 0.25, '1/X' => 0.08, '1/2' => 0.05,
                'X/1' => 0.12, 'X/X' => 0.15, 'X/2' => 0.08,
                '2/1' => 0.05, '2/X' => 0.07, '2/2' => 0.15
            ];
        }
    
        // Enhanced prediction logic based on team analysis
        $analysis = $this->analyzeTeamStrengths($h2h, $fixture);
        
        // Apply team strength weighting
        if ($analysis['home_stronger']) {
            // Favor home-winning scenarios
            $homeResults = ['1/1', '1/X', 'X/1'];
            foreach ($homeResults as $result) {
                if (isset($weighted[$result])) {
                    $weighted[$result] *= (1.2 + ($analysis['strength_diff'] * 0.3));
                }
            }
        } elseif ($analysis['away_stronger']) {
            // Favor away-winning scenarios
            $awayResults = ['2/2', '2/X', 'X/2'];
            foreach ($awayResults as $result) {
                if (isset($weighted[$result])) {
                    $weighted[$result] *= (1.2 + ($analysis['strength_diff'] * 0.3));
                }
            }
        } else {
            // Balanced teams - favor draw scenarios and consistent results
            $balancedResults = ['X/X', '1/1', '2/2', 'X/1', 'X/2'];
            foreach ($balancedResults as $result) {
                if (isset($weighted[$result])) {
                    $weighted[$result] *= 1.15;
                }
            }
        }
    
        // Apply league-based adjustments
        $leagueAdjustments = $this->getLeagueCharacteristics($fixture->league_name ?? '');
        foreach ($leagueAdjustments as $result => $multiplier) {
            if (isset($weighted[$result])) {
                $weighted[$result] *= $multiplier;
            }
        }
    
        // Historical pattern analysis
        $htftPattern = $this->analyzeHtftPatterns($h2h);
        foreach ($htftPattern as $pattern => $frequency) {
            if (isset($weighted[$pattern]) && $frequency > 0.3) {
                $weighted[$pattern] *= (1 + $frequency);
            }
        }
    
        // Make prediction
        $prediction = $this->weightedRandom($weighted);
        
        // Find corresponding odd value
        $oddValue = null;
        foreach ($htft['values'] as $val) {
            if ($val['value'] === $prediction) {
                $oddValue = $val['odd'];
                break;
            }
        }
    
        // Calculate confidence based on analysis strength
        $confidence = $this->calculateHtftConfidence($analysis, $htftPattern, $weighted[$prediction] ?? 0.1);
    
        return [
            'prediction' => $prediction,
            'confidence' => $confidence,
            'odd' => $oddValue
        ];
    }
    
    // Helper method to analyze team strengths
    private function analyzeTeamStrengths($h2h, $fixture)
    {
        $homeWins = $this->countH2HWins($h2h, $fixture->home_team_id ?? 0);
        $awayWins = $this->countH2HWins($h2h, $fixture->away_team_id ?? 0);
        $totalMatches = count($h2h);
        $draws = $totalMatches - $homeWins - $awayWins;
    
        $homeWinRate = $totalMatches > 0 ? $homeWins / $totalMatches : 0.33;
        $awayWinRate = $totalMatches > 0 ? $awayWins / $totalMatches : 0.33;
        
        $strengthDiff = abs($homeWinRate - $awayWinRate);
        
        return [
            'home_stronger' => $homeWinRate > $awayWinRate && $strengthDiff > 0.2,
            'away_stronger' => $awayWinRate > $homeWinRate && $strengthDiff > 0.2,
            'balanced' => $strengthDiff <= 0.2,
            'strength_diff' => $strengthDiff,
            'home_win_rate' => $homeWinRate,
            'away_win_rate' => $awayWinRate
        ];
    }
    
    // Helper method to get league characteristics
    private function getLeagueCharacteristics($leagueName)
    {
        $leagueName = strtolower($leagueName);
        
        // Different leagues have different characteristics
        if (strpos($leagueName, 'premier') !== false || strpos($leagueName, 'bundesliga') !== false) {
            // High-scoring, attacking leagues
            return ['1/1' => 1.1, '2/2' => 1.1, '1/2' => 1.05, '2/1' => 1.05];
        } elseif (strpos($leagueName, 'serie a') !== false || strpos($leagueName, 'ligue 1') !== false) {
            // More tactical, defensive leagues
            return ['X/X' => 1.15, '1/X' => 1.1, 'X/2' => 1.1, 'X/1' => 1.1];
        } elseif (strpos($leagueName, 'la liga') !== false) {
            // Balanced but home advantage
            return ['1/1' => 1.1, 'X/1' => 1.05, '1/X' => 1.05];
        } else {
            // Default balanced approach
            return ['1/1' => 1.05, 'X/X' => 1.05, '2/2' => 1.05];
        }
    }
    
    // Helper method to analyze HT/FT patterns from H2H
    private function analyzeHtftPatterns($h2h)
    {
        $patterns = [
            '1/1' => 0, '1/X' => 0, '1/2' => 0,
            'X/1' => 0, 'X/X' => 0, 'X/2' => 0,
            '2/1' => 0, '2/X' => 0, '2/2' => 0
        ];
        
        foreach ($h2h as $match) {
            $htHome = $match['score']['halftime']['home'] ?? rand(0, 2);
            $htAway = $match['score']['halftime']['away'] ?? rand(0, 2);
            $ftHome = $match['goals']['home'] ?? rand(0, 3);
            $ftAway = $match['goals']['away'] ?? rand(0, 3);
            
            // Determine HT result
            $htResult = $htHome > $htAway ? '1' : ($htHome < $htAway ? '2' : 'X');
            // Determine FT result
            $ftResult = $ftHome > $ftAway ? '1' : ($ftHome < $ftAway ? '2' : 'X');
            
            $pattern = $htResult . '/' . $ftResult;
            if (isset($patterns[$pattern])) {
                $patterns[$pattern]++;
            }
        }
        
        // Convert counts to frequencies
        $totalMatches = count($h2h);
        if ($totalMatches > 0) {
            foreach ($patterns as $pattern => $count) {
                $patterns[$pattern] = $count / $totalMatches;
            }
        }
        
        return $patterns;
    }
    
    // Helper method to generate realistic H2H data
    private function generateRealisticH2H($fixture)
    {
        $h2h = [];
        $matchCount = rand(3, 8); // Generate 3-8 historical matches
        
        for ($i = 0; $i < $matchCount; $i++) {
            // Generate realistic scorelines
            $homeGoals = $this->generateRealisticScore();
            $awayGoals = $this->generateRealisticScore();
            $htHome = min($homeGoals, rand(0, 2));
            $htAway = min($awayGoals, rand(0, 2));
            
            $h2h[] = [
                'teams' => [
                    'home' => ['id' => $fixture->home_team_id ?? 1, 'winner' => $homeGoals > $awayGoals],
                    'away' => ['id' => $fixture->away_team_id ?? 2, 'winner' => $awayGoals > $homeGoals]
                ],
                'goals' => ['home' => $homeGoals, 'away' => $awayGoals],
                'score' => [
                    'halftime' => ['home' => $htHome, 'away' => $htAway],
                    'fulltime' => ['home' => $homeGoals, 'away' => $awayGoals]
                ]
            ];
        }
        
        return $h2h;
    }
    
    // Helper method to generate realistic HT/FT odds
    private function generateRealisticHtftOdds()
    {
        return [
            'values' => [
                ['value' => '1/1', 'odd' => rand(350, 600) / 100],  // 3.50 - 6.00
                ['value' => '1/X', 'odd' => rand(800, 1500) / 100], // 8.00 - 15.00
                ['value' => '1/2', 'odd' => rand(1500, 3000) / 100], // 15.00 - 30.00
                ['value' => 'X/1', 'odd' => rand(600, 1200) / 100], // 6.00 - 12.00
                ['value' => 'X/X', 'odd' => rand(800, 1800) / 100], // 8.00 - 18.00
                ['value' => 'X/2', 'odd' => rand(800, 1500) / 100], // 8.00 - 15.00
                ['value' => '2/1', 'odd' => rand(1500, 3000) / 100], // 15.00 - 30.00
                ['value' => '2/X', 'odd' => rand(1200, 2500) / 100], // 12.00 - 25.00
                ['value' => '2/2', 'odd' => rand(350, 600) / 100],  // 3.50 - 6.00
            ]
        ];
    }
    
    // Helper method to generate realistic score
    private function generateRealisticScore()
    {
        $weights = [0 => 25, 1 => 35, 2 => 20, 3 => 12, 4 => 5, 5 => 2, 6 => 1];
        return $this->weightedRandom($weights);
    }
    
    // Helper method to calculate confidence based on analysis
    private function calculateHtftConfidence($analysis, $patterns, $predictionWeight)
    {
        $baseConfidence = 45;
        
        // Increase confidence based on team strength difference
        if ($analysis['home_stronger'] || $analysis['away_stronger']) {
            $baseConfidence += ($analysis['strength_diff'] * 20);
        }
        
        // Increase confidence based on historical pattern strength
        $maxPattern = max($patterns);
        if ($maxPattern > 0.3) {
            $baseConfidence += ($maxPattern * 15);
        }
        
        // Increase confidence based on prediction weight
        $baseConfidence += ($predictionWeight * 10);
        
        // Ensure confidence is within reasonable bounds
        return min(85, max(40, (int) $baseConfidence));
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
