<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fixture;
use Illuminate\Support\Facades\Http;

class FetchFixturesFull extends Command
{
    protected $signature = 'fetch:fixtures-full';
    protected $description = 'Fetch fixtures with odds, head2head, and statistics data from API-Sports';

    public function handle()
    {
        $apiKey = env('APISPORTS_KEY'); // store your key in .env
        $fixtures = Fixture::where('odds_fetched', false)->limit(30)->get(); // limit to prevent overload

        if ($fixtures->isEmpty()) {
            $this->info('✅ All fixtures already have odds, head2head, and stats fetched.');
            return;
        }

        foreach ($fixtures as $fixture) {
            $this->info("⚽ Fetching full data for fixture #{$fixture->fixture_id}");

            try {
                // Fetch odds
                $oddsRes = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/odds', [
                    'fixture' => $fixture->fixture_id,
                ]);

                $odds = $oddsRes->json('response', []);

                // Fetch head2head
                $h2hRes = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/fixtures/headtohead', [
                    'h2h' => $fixture->home_team_id . '-' . $fixture->away_team_id,
                ]);

                $h2h = $h2hRes->json('response', []);

                // Fetch statistics for both teams
                $homeStatsRes = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/fixtures/statistics', [
                    'fixture' => $fixture->fixture_id,
                    'team' => $fixture->home_team_id,
                ]);

                $awayStatsRes = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/fixtures/statistics', [
                    'fixture' => $fixture->fixture_id,
                    'team' => $fixture->away_team_id,
                ]);

                $statistics = [
                    'home' => $homeStatsRes->json('response', []),
                    'away' => $awayStatsRes->json('response', []),
                ];

                // Save everything to fixture
                $fixture->update([
                    'odds' => $odds,
                    'head2head' => $h2h,
                    'statistics' => $statistics,
                    'odds_fetched' => true,
                ]);

                $this->info("✅ Fixture #{$fixture->fixture_id} updated successfully!");
            } catch (\Exception $e) {
                $this->error("❌ Error for fixture #{$fixture->fixture_id}: " . $e->getMessage());
                continue;
            }

            sleep(2); // prevent rate limiting
        }

        $this->info('🎯 Done fetching all available fixtures!');
    }
}
