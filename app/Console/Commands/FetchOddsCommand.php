<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchOddsCommand extends Command
{
    protected $signature = 'fetch:odds {--limit=100}';
    protected $description = 'Fetch odds for all fixtures that do not have odds yet (batch processing).';

    public function handle()
    {
        $apiKey = env('API_FOOTBALL_KEY');
        $baseUrl = 'https://v3.football.api-sports.io';

        if (empty($apiKey)) {
            $this->error('❌ API_FOOTBALL_KEY not set in .env');
            return;
        }

        $batchSize = (int) $this->option('limit');
        $page = 1;
        $totalProcessed = 0;

        do {
            $fixtures = Fixture::where('has_odds', false)
                ->orderBy('id')
                ->skip(($page - 1) * $batchSize)
                ->take($batchSize)
                ->get();

            if ($fixtures->isEmpty()) {
                if ($totalProcessed === 0) {
                    $this->info('✅ All fixtures already have odds.');
                }
                break;
            }

            $this->info("🧩 Processing batch #{$page} (count: {$fixtures->count()})");

            foreach ($fixtures as $fixture) {
                $fixtureId = $fixture->fixture_id;
                $this->line("⚽ Fetching odds for fixture #{$fixtureId}...");

                $odds = $this->tryFetchOdds($baseUrl, $apiKey, $fixtureId);

                // Save odds and mark as processed
                $fixture->update([
                    'odds' => $odds['response'] ?? [],
                    'has_odds' => true,
                ]);

                $this->info("💾 Saved odds for fixture {$fixtureId}");
                $totalProcessed++;

                // Gentle delay
                sleep(1);
            }

            $page++;
            $this->line("🕐 Batch {$page} complete. Waiting before next batch...");
            sleep(3); // Pause between batches to prevent API bans

        } while (true);

        $this->info("🎯 Completed fetching odds for {$totalProcessed} fixtures.");
    }

    /**
     * Try fetching odds using multiple bookmakers (11, then 1–16 fallback)
     */
    private function tryFetchOdds($baseUrl, $apiKey, $fixtureId)
    {
        $bookmakers = range(1, 16);
        array_unshift($bookmakers, 11);
        $bookmakers = array_unique($bookmakers);

        foreach ($bookmakers as $bookmakerId) {
            $response = Http::withHeaders([
                'x-apisports-key' => $apiKey,
            ])->get("{$baseUrl}/odds", [
                'fixture' => $fixtureId,
                'bookmaker' => $bookmakerId,
            ]);

            if ($response->failed()) {
                $this->warn("❌ HTTP {$response->status()} fetching fixture {$fixtureId}, bookmaker {$bookmakerId}");
                Log::error("FetchOddsCommand HTTP {$response->status()} for fixture {$fixtureId}");
                continue;
            }

            $data = $response->json();

            if (!empty($data['response'])) {
                $this->info("✅ Found odds from bookmaker {$bookmakerId} for fixture {$fixtureId}");
                return $data;
            }

            sleep(1);
        }

        $this->warn("⚠️ No odds found for fixture {$fixtureId}");
        return [];
    }
}
