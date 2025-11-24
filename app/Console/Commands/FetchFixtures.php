<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FetchFixtures extends Command
{
    protected $signature = 'fetch:fixtures {days=3}';
    protected $description = 'Fetch fixtures (and odds/head2head/statistics) for today and next N days from API-Sports';

    public function handle()
    {
        $apiKey = env('FOOTBALL_API_KEY');
        $daysToFetch = (int) $this->argument('days');
        $startDate = Carbon::today();

        $this->info("🏁 Starting fetch for {$daysToFetch} day(s) from {$startDate->toDateString()}...");

        for ($i = 0; $i <= $daysToFetch; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            $this->info("\n📅 Fetching fixtures for {$date}...");

            try {
                $response = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/fixtures', [
                    'date' => $date,
                ]);

                $data = $response->json();

                if (empty($data['response'])) {
                    $this->warn("⚠️ No fixtures found for {$date}.");
                    continue;
                }

                foreach ($data['response'] as $item) {
                    $fixtureData = $item['fixture'];
                    $leagueData = $item['league'];
                    $teams = $item['teams'];
                    $goals = $item['goals'];
                    $score = $item['score'];

                    $fixtureDate = Carbon::parse($fixtureData['date'])->format('Y-m-d H:i:s');

                    $fixture = Fixture::updateOrCreate(
                        ['fixture_id' => $fixtureData['id']],
                        [
                            'referee' => $fixtureData['referee'],
                            'timezone' => $fixtureData['timezone'],
                            'date' => $fixtureDate,
                            'timestamp' => $fixtureData['timestamp'],
                            'venue_name' => $fixtureData['venue']['name'] ?? null,
                            'venue_city' => $fixtureData['venue']['city'] ?? null,
                            'status_long' => $fixtureData['status']['long'] ?? null,
                            'status_short' => $fixtureData['status']['short'] ?? null,
                            'elapsed' => $fixtureData['status']['elapsed'] ?? null,
                            'league_id' => $leagueData['id'],
                            'league_name' => $leagueData['name'],
                            'league_country' => $leagueData['country'],
                            'league_logo' => $leagueData['logo'],
                            'league_flag' => $leagueData['flag'],
                            'league_round' => $leagueData['round'],
                            'league_season' => $leagueData['season'],
                            'home_team_id' => $teams['home']['id'],
                            'home_team_name' => $teams['home']['name'],
                            'home_team_logo' => $teams['home']['logo'],
                            'home_team_winner' => $teams['home']['winner'],
                            'away_team_id' => $teams['away']['id'],
                            'away_team_name' => $teams['away']['name'],
                            'away_team_logo' => $teams['away']['logo'],
                            'away_team_winner' => $teams['away']['winner'],
                            'goals_home' => $goals['home'],
                            'goals_away' => $goals['away'],
                            'halftime_home' => $score['halftime']['home'],
                            'halftime_away' => $score['halftime']['away'],
                            'fulltime_home' => $score['fulltime']['home'],
                            'fulltime_away' => $score['fulltime']['away'],
                        ]
                    );

                    if ($fixture->odds_fetched) {
                        $this->line("⏩ Skipping fixture #{$fixture->fixture_id}, already processed.");
                        continue;
                    }

                    $this->info("⚽ Fetching odds/statistics for fixture #{$fixture->fixture_id}");

                    // === Fetch Odds ===
                    $odds = $this->retryApiCall($apiKey, 'https://v3.football.api-sports.io/odds', [
                        'fixture' => $fixture->fixture_id,
                    ]);

                    // === Fetch Head2Head ===
                    $h2h = $this->retryApiCall($apiKey, 'https://v3.football.api-sports.io/fixtures/headtohead', [
                        'h2h' => "{$fixture->home_team_id}-{$fixture->away_team_id}",
                    ]);

                    // === Fetch Statistics ===
                    $homeStats = $this->retryApiCall($apiKey, 'https://v3.football.api-sports.io/fixtures/statistics', [
                        'fixture' => $fixture->fixture_id,
                        'team' => $fixture->home_team_id,
                    ]);

                    $awayStats = $this->retryApiCall($apiKey, 'https://v3.football.api-sports.io/fixtures/statistics', [
                        'fixture' => $fixture->fixture_id,
                        'team' => $fixture->away_team_id,
                    ]);

                    $statistics = [
                        'home' => $homeStats['response'] ?? [],
                        'away' => $awayStats['response'] ?? [],
                    ];

                    $fixture->update([
                        'odds' => $odds['response'] ?? [],
                        'head2head' => $h2h['response'] ?? [],
                        'statistics' => $statistics,
                        'odds_fetched' => true,
                    ]);

                    $this->info("✅ Updated fixture #{$fixture->fixture_id} ({$fixture->home_team_name} vs {$fixture->away_team_name})");

                    sleep(2); // small delay for API rate safety
                }
            } catch (\Exception $e) {
                $this->error("❌ Error fetching for {$date}: " . $e->getMessage());
            }
        }

        $this->info("\n🎯 Done fetching fixtures for {$daysToFetch} day(s).");
    }

    /**
     * Retry API call up to 3 times before giving up.
     */
    private function retryApiCall($apiKey, $url, $params, $maxRetries = 3)
    {
        $attempt = 0;
        $data = null;

        while ($attempt < $maxRetries) {
            try {
                $attempt++;
                $response = Http::withHeaders(['x-apisports-key' => $apiKey])->get($url, $params);
                $data = $response->json();

                if (!empty($data['response'])) {
                    return $data;
                }

                $this->warn("⚠️ Empty response from {$url}, retrying... (Attempt {$attempt})");
                sleep(1);
            } catch (\Exception $e) {
                $this->warn("⚠️ Attempt {$attempt} failed: " . $e->getMessage());
                sleep(1);
            }
        }

        $this->error("❌ Failed to fetch from {$url} after {$maxRetries} attempts.");
        return [];
    }
}
