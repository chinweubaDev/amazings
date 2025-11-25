<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\League>
 */
class LeagueFactory extends Factory
{
    protected $model = League::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $leagues = [
            ['id' => 39, 'name' => 'Premier League', 'country' => 'England', 'code' => 'GB', 'top' => true],
            ['id' => 140, 'name' => 'La Liga', 'country' => 'Spain', 'code' => 'ES', 'top' => true],
            ['id' => 78, 'name' => 'Bundesliga', 'country' => 'Germany', 'code' => 'DE', 'top' => true],
            ['id' => 135, 'name' => 'Serie A', 'country' => 'Italy', 'code' => 'IT', 'top' => true],
            ['id' => 61, 'name' => 'Ligue 1', 'country' => 'France', 'code' => 'FR', 'top' => true],
            ['id' => 94, 'name' => 'Primeira Liga', 'country' => 'Portugal', 'code' => 'PT', 'top' => true],
            ['id' => 88, 'name' => 'Eredivisie', 'country' => 'Netherlands', 'code' => 'NL', 'top' => true],
            ['id' => 144, 'name' => 'Jupiler Pro League', 'country' => 'Belgium', 'code' => 'BE', 'top' => true],
        ];

        $league = $this->faker->randomElement($leagues);
        $currentYear = date('Y');

        return [
            'league_id' => $league['id'],
            'name' => $league['name'],
            'type' => 'League',
            'logo' => "https://media.api-sports.io/football/leagues/{$league['id']}.png",
            'country_name' => $league['country'],
            'country_code' => $league['code'],
            'country_flag' => "https://media.api-sports.io/flags/{$league['code']}.svg",
            'is_top_league' => $league['top'],
            'seasons' => [
                ['year' => $currentYear - 1, 'start' => ($currentYear - 1) . '-08-01', 'end' => $currentYear . '-05-31'],
                ['year' => $currentYear, 'start' => $currentYear . '-08-01', 'end' => ($currentYear + 1) . '-05-31'],
            ],
        ];
    }

    /**
     * Create a top league.
     */
    public function topLeague(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_top_league' => true,
        ]);
    }

    /**
     * Create a specific league.
     */
    public function league(int $leagueId, string $name, string $countryCode): static
    {
        return $this->state(fn (array $attributes) => [
            'league_id' => $leagueId,
            'name' => $name,
            'country_code' => $countryCode,
            'logo' => "https://media.api-sports.io/football/leagues/{$leagueId}.png",
        ]);
    }
}
