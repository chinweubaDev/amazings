<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use Illuminate\Support\Facades\Log;

class CountriesSeeder extends Seeder
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

        $this->command->info('Fetching countries from API-Sports...');

        $response = Http::withHeaders([
            'x-apisports-key' => $apiKey,
        ])->get('https://v3.football.api-sports.io/countries');

        if ($response->failed()) {
            $this->command->error('Failed to fetch countries: ' . $response->body());
            Log::error('Failed to fetch countries', ['response' => $response->body()]);
            return;
        }

        $data = $response->json();

        if (!isset($data['response']) || !is_array($data['response'])) {
            $this->command->error('Invalid response format.');
            return;
        }

        $countries = $data['response'];
        $count = count($countries);
        $this->command->info("Found {$count} countries. Seeding countries...");

        $bar = $this->command->getOutput()->createProgressBar($count);
        $bar->start();

        foreach ($countries as $item) {
            Country::updateOrCreate(
                ['name' => $item['name']],
                [
                    'code' => $item['code'],
                    'flag' => $item['flag'],
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Countries seeded successfully!');
    }
}
