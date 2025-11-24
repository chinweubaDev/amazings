<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\League;

class FetchLeaguesCommand extends Command
{
    protected $signature = 'fetch:leagues';
    protected $description = 'Fetch and populate football leagues into the database';

    public function handle()
    {
        $apiKey = env('FOOTBALL_API_KEY');
        $baseUrl = 'https://v3.football.api-sports.io';

        $this->info("🏆 Fetching leagues from API...");

        $response = Http::withHeaders([
            'x-apisports-key' => $apiKey,
        ])->get("{$baseUrl}/leagues");

        if ($response->failed()) {
            $this->error("❌ Failed to fetch leagues: " . $response->body());
            return;
        }

        $data = $response->json();

        foreach ($data['response'] as $leagueData) {
            $league = $leagueData['league'];
            $country = $leagueData['country'];
            $seasons = $leagueData['seasons'] ?? [];

            League::updateOrCreate(
                ['league_id' => $league['id']],
                [
                    'name' => $league['name'],
                    'type' => $league['type'],
                    'logo' => $league['logo'],
                    'country_name' => $country['name'] ?? null,
                    'country_code' => $country['code'] ?? null,
                    'country_flag' => $country['flag'] ?? null,
                    'seasons' => json_encode($seasons),
                ]
            );
        }

        $this->info("✅ Leagues table updated successfully (" . count($data['response']) . " leagues).");
    }
}
