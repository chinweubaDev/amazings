<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\League;
use App\Models\Fixture;
use App\Models\Odd;
use Illuminate\Database\Seeder;

class FixtureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create countries
        $countries = [
            ['name' => 'England', 'code' => 'GB', 'flag' => 'https://media.api-sports.io/flags/gb.svg'],
            ['name' => 'Spain', 'code' => 'ES', 'flag' => 'https://media.api-sports.io/flags/es.svg'],
            ['name' => 'Germany', 'code' => 'DE', 'flag' => 'https://media.api-sports.io/flags/de.svg'],
            ['name' => 'Italy', 'code' => 'IT', 'flag' => 'https://media.api-sports.io/flags/it.svg'],
            ['name' => 'France', 'code' => 'FR', 'flag' => 'https://media.api-sports.io/flags/fr.svg'],
        ];

        foreach ($countries as $countryData) {
            Country::firstOrCreate(
                ['code' => $countryData['code']],
                $countryData
            );
        }

        // Create top leagues
        $leagues = [
            [
                'league_id' => 39,
                'name' => 'Premier League',
                'type' => 'League',
                'logo' => 'https://media.api-sports.io/football/leagues/39.png',
                'country_name' => 'England',
                'country_code' => 'GB',
                'country_flag' => 'https://media.api-sports.io/flags/gb.svg',
                'is_top_league' => true,
                'seasons' => [
                    ['year' => 2024, 'start' => '2024-08-01', 'end' => '2025-05-31'],
                    ['year' => 2025, 'start' => '2025-08-01', 'end' => '2026-05-31'],
                ],
            ],
            [
                'league_id' => 140,
                'name' => 'La Liga',
                'type' => 'League',
                'logo' => 'https://media.api-sports.io/football/leagues/140.png',
                'country_name' => 'Spain',
                'country_code' => 'ES',
                'country_flag' => 'https://media.api-sports.io/flags/es.svg',
                'is_top_league' => true,
                'seasons' => [
                    ['year' => 2024, 'start' => '2024-08-01', 'end' => '2025-05-31'],
                    ['year' => 2025, 'start' => '2025-08-01', 'end' => '2026-05-31'],
                ],
            ],
            [
                'league_id' => 78,
                'name' => 'Bundesliga',
                'type' => 'League',
                'logo' => 'https://media.api-sports.io/football/leagues/78.png',
                'country_name' => 'Germany',
                'country_code' => 'DE',
                'country_flag' => 'https://media.api-sports.io/flags/de.svg',
                'is_top_league' => true,
                'seasons' => [
                    ['year' => 2024, 'start' => '2024-08-01', 'end' => '2025-05-31'],
                    ['year' => 2025, 'start' => '2025-08-01', 'end' => '2026-05-31'],
                ],
            ],
            [
                'league_id' => 135,
                'name' => 'Serie A',
                'type' => 'League',
                'logo' => 'https://media.api-sports.io/football/leagues/135.png',
                'country_name' => 'Italy',
                'country_code' => 'IT',
                'country_flag' => 'https://media.api-sports.io/flags/it.svg',
                'is_top_league' => true,
                'seasons' => [
                    ['year' => 2024, 'start' => '2024-08-01', 'end' => '2025-05-31'],
                    ['year' => 2025, 'start' => '2025-08-01', 'end' => '2026-05-31'],
                ],
            ],
            [
                'league_id' => 61,
                'name' => 'Ligue 1',
                'type' => 'League',
                'logo' => 'https://media.api-sports.io/football/leagues/61.png',
                'country_name' => 'France',
                'country_code' => 'FR',
                'country_flag' => 'https://media.api-sports.io/flags/fr.svg',
                'is_top_league' => true,
                'seasons' => [
                    ['year' => 2024, 'start' => '2024-08-01', 'end' => '2025-05-31'],
                    ['year' => 2025, 'start' => '2025-08-01', 'end' => '2026-05-31'],
                ],
            ],
        ];

        foreach ($leagues as $leagueData) {
            League::firstOrCreate(
                ['league_id' => $leagueData['league_id']],
                $leagueData
            );
        }

        $this->command->info('Countries and leagues created successfully.');

        // Create fixtures
        $this->command->info('Creating fixtures...');

        // Create 20 finished fixtures
        Fixture::factory()
            ->count(20)
            ->finished()
            ->create()
            ->each(function ($fixture) {
                // Add odds to some finished fixtures
                if (rand(0, 1)) {
                    Odd::factory()
                        ->count(rand(3, 6))
                        ->forFixture($fixture->fixture_id)
                        ->create();
                }
            });

        $this->command->info('Created 20 finished fixtures.');

        // Create 15 upcoming fixtures
        Fixture::factory()
            ->count(15)
            ->upcoming()
            ->create()
            ->each(function ($fixture) {
                // Add odds to most upcoming fixtures
                if (rand(0, 100) > 30) {
                    Odd::factory()
                        ->count(rand(3, 9))
                        ->forFixture($fixture->fixture_id)
                        ->create();
                }
            });

        $this->command->info('Created 15 upcoming fixtures.');

        // Create 10 fixtures with various statuses
        Fixture::factory()
            ->count(10)
            ->create()
            ->each(function ($fixture) {
                // Add odds to some fixtures
                if (rand(0, 1)) {
                    Odd::factory()
                        ->count(rand(3, 6))
                        ->forFixture($fixture->fixture_id)
                        ->create();
                }
            });

        $this->command->info('Created 10 additional fixtures with various statuses.');

        $this->command->info('Fixture seeding completed successfully!');
        $this->command->info('Total fixtures: ' . Fixture::count());
        $this->command->info('Total odds: ' . Odd::count());
    }
}
