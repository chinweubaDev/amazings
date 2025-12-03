<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\League;

class TopLeaguesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topLeagues = [
            ['name' => 'Bundesliga', 'country' => 'Germany'],
            ['name' => 'FA Cup', 'country' => 'England'],
            ['name' => 'La Liga', 'country' => 'Spain'],
            ['name' => 'Ligue 1', 'country' => 'France'],
            ['name' => 'Eredivisie', 'country' => 'Netherlands'],
            ['name' => 'Premier League', 'country' => 'England'],
            ['name' => 'Premiership', 'country' => 'Scotland'],
            ['name' => 'Primeira Liga', 'country' => 'Portugal'],
            ['name' => 'Serie A', 'country' => 'Italy'],
            ['name' => 'UEFA Europa League', 'country' => 'World'], // API-Sports often lists these under 'World' or specific regions
            ['name' => 'CAF Champions League', 'country' => 'World'],
            ['name' => 'UEFA Europa Conference League', 'country' => 'World'],
        ];

        $this->command->info('Marking top leagues...');

        foreach ($topLeagues as $item) {
            // Try to find by name and country first
            $league = League::where('name', $item['name'])
                ->where('country_name', $item['country'])
                ->first();

            // If not found, try by name only (some might have different country names in DB vs provided list)
            if (!$league) {
                 $league = League::where('name', $item['name'])->first();
            }

            if ($league) {
                $league->update(['is_top_league' => true]);
                $this->command->info("Marked {$league->name} ({$league->country_name}) as top league.");
            } else {
                $this->command->warn("League not found: {$item['name']} ({$item['country']})");
            }
        }

        $this->command->info('Top leagues marked successfully!');
    }
}
