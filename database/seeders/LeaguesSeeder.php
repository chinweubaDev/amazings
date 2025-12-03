<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\League;
use Illuminate\Support\Facades\Log;

class LeaguesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apiKey = config('services.football_api.key');

        if (empty($apiKey)) {
            $this->command->error('API Key is missing. Please set FOOTBALL_API_KEY in your .env file.');
            return;
        }

        $this->command->info('Fetching leagues from API-Sports...');

        $response = Http::withHeaders([
            'x-apisports-key' => $apiKey,
        ])->get('https://v3.football.api-sports.io/leagues');

        if ($response->failed()) {
            $this->command->error('Failed to fetch leagues: ' . $response->body());
            Log::error('Failed to fetch leagues', ['response' => $response->body()]);
            return;
        }

        $data = $response->json();

        if (!isset($data['response']) || !is_array($data['response'])) {
            $this->command->error('Invalid response format.');
            return;
        }

        $leagues = $data['response'];
        $count = count($leagues);
        $this->command->info("Found {$count} leagues. specific leagues...");

        $bar = $this->command->getOutput()->createProgressBar($count);
        $bar->start();

        foreach ($leagues as $item) {
            $leagueData = $item['league'];
            $countryData = $item['country'];
            $seasonsData = $item['seasons'];

            League::updateOrCreate(
                ['league_id' => $leagueData['id']],
                [
                    'name' => $leagueData['name'],
                    'type' => $leagueData['type'],
                    'logo' => $leagueData['logo'],
                    'country_name' => $countryData['name'],
                    'country_code' => $countryData['code'],
                    'country_flag' => $countryData['flag'],
                    'seasons' => $seasonsData,
                    // 'is_top_league' => false, // Default to false, can be updated manually or by logic
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Leagues seeded successfully!');
    }
}
