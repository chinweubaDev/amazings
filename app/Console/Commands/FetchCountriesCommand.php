<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Country;

class FetchCountriesCommand extends Command
{
    protected $signature = 'fetch:countries';
    protected $description = 'Fetch and populate football countries into the database';

    public function handle()
    {
        $apiKey = env('FOOTBALL_API_KEY');
        $baseUrl = 'https://v3.football.api-sports.io';

        $this->info("🌍 Fetching countries from API...");

        $response = Http::withHeaders([
            'x-apisports-key' => $apiKey,
        ])->get("{$baseUrl}/countries");

        if ($response->failed()) {
            $this->error("❌ Failed to fetch countries: " . $response->body());
            return;
        }

        $data = $response->json();

        foreach ($data['response'] as $country) {
            Country::updateOrCreate(
                ['name' => $country['name']],
                [
                    'code' => $country['code'] ?? null,
                    'flag' => $country['flag'] ?? null,
                ]
            );
        }

        $this->info("✅ Countries table updated successfully (" . count($data['response']) . " countries).");
    }
}
