<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teams = [
            ['id' => 33, 'name' => 'Manchester United', 'logo' => 'https://media.api-sports.io/football/teams/33.png'],
            ['id' => 34, 'name' => 'Newcastle', 'logo' => 'https://media.api-sports.io/football/teams/34.png'],
            ['id' => 40, 'name' => 'Liverpool', 'logo' => 'https://media.api-sports.io/football/teams/40.png'],
            ['id' => 42, 'name' => 'Arsenal', 'logo' => 'https://media.api-sports.io/football/teams/42.png'],
            ['id' => 47, 'name' => 'Tottenham', 'logo' => 'https://media.api-sports.io/football/teams/47.png'],
            ['id' => 50, 'name' => 'Manchester City', 'logo' => 'https://media.api-sports.io/football/teams/50.png'],
            ['id' => 49, 'name' => 'Chelsea', 'logo' => 'https://media.api-sports.io/football/teams/49.png'],
            ['id' => 529, 'name' => 'Barcelona', 'logo' => 'https://media.api-sports.io/football/teams/529.png'],
            ['id' => 541, 'name' => 'Real Madrid', 'logo' => 'https://media.api-sports.io/football/teams/541.png'],
            ['id' => 530, 'name' => 'Atletico Madrid', 'logo' => 'https://media.api-sports.io/football/teams/530.png'],
            ['id' => 157, 'name' => 'Bayern Munich', 'logo' => 'https://media.api-sports.io/football/teams/157.png'],
            ['id' => 165, 'name' => 'Borussia Dortmund', 'logo' => 'https://media.api-sports.io/football/teams/165.png'],
            ['id' => 489, 'name' => 'AC Milan', 'logo' => 'https://media.api-sports.io/football/teams/489.png'],
            ['id' => 492, 'name' => 'Napoli', 'logo' => 'https://media.api-sports.io/football/teams/492.png'],
            ['id' => 496, 'name' => 'Juventus', 'logo' => 'https://media.api-sports.io/football/teams/496.png'],
            ['id' => 497, 'name' => 'AS Roma', 'logo' => 'https://media.api-sports.io/football/teams/497.png'],
            ['id' => 85, 'name' => 'Paris Saint Germain', 'logo' => 'https://media.api-sports.io/football/teams/85.png'],
            ['id' => 91, 'name' => 'Monaco', 'logo' => 'https://media.api-sports.io/football/teams/91.png'],
        ];

        $homeTeam = $this->faker->randomElement($teams);
        $awayTeam = $this->faker->randomElement(array_filter($teams, fn($t) => $t['id'] !== $homeTeam['id']));

        $date = $this->faker->dateTimeBetween('-7 days', '+30 days');
        $timestamp = $date->getTimestamp();

        $statuses = [
            ['long' => 'Match Finished', 'short' => 'FT'],
            ['long' => 'Not Started', 'short' => 'NS'],
            ['long' => 'First Half', 'short' => '1H'],
            ['long' => 'Halftime', 'short' => 'HT'],
            ['long' => 'Second Half', 'short' => '2H'],
        ];
        $status = $this->faker->randomElement($statuses);

        $isFinished = $status['short'] === 'FT';
        $goalsHome = $isFinished ? $this->faker->numberBetween(0, 5) : null;
        $goalsAway = $isFinished ? $this->faker->numberBetween(0, 5) : null;

        // Default league data (will be overridden if league exists)
        $defaultLeagues = [
            ['id' => 39, 'name' => 'Premier League', 'country' => 'England', 'flag' => 'https://media.api-sports.io/flags/gb.svg'],
            ['id' => 140, 'name' => 'La Liga', 'country' => 'Spain', 'flag' => 'https://media.api-sports.io/flags/es.svg'],
            ['id' => 78, 'name' => 'Bundesliga', 'country' => 'Germany', 'flag' => 'https://media.api-sports.io/flags/de.svg'],
            ['id' => 135, 'name' => 'Serie A', 'country' => 'Italy', 'flag' => 'https://media.api-sports.io/flags/it.svg'],
            ['id' => 61, 'name' => 'Ligue 1', 'country' => 'France', 'flag' => 'https://media.api-sports.io/flags/fr.svg'],
        ];
        $leagueData = $this->faker->randomElement($defaultLeagues);

        return [
            'fixture_id' => $this->faker->unique()->numberBetween(100000, 999999),
            'referee' => $this->faker->optional(0.7)->name(),
            'timezone' => 'UTC',
            'date' => $date,
            'timestamp' => $timestamp,
            'venue_name' => $this->faker->optional(0.8)->company() . ' Stadium',
            'venue_city' => $this->faker->optional(0.8)->city(),
            'status_long' => $status['long'],
            'status_short' => $status['short'],
            'elapsed' => $isFinished ? 90 : ($status['short'] === 'NS' ? null : $this->faker->numberBetween(1, 90)),

            // League info
            'league_id' => $leagueData['id'],
            'league_name' => $leagueData['name'],
            'league_country' => $leagueData['country'],
            'league_logo' => "https://media.api-sports.io/football/leagues/{$leagueData['id']}.png",
            'league_flag' => $leagueData['flag'],
            'league_round' => 'Regular Season - ' . $this->faker->numberBetween(1, 38),
            'league_season' => date('Y'),


            // Home team
            'home_team_id' => $homeTeam['id'],
            'home_team_name' => $homeTeam['name'],
            'home_team_logo' => $homeTeam['logo'],
            'home_team_winner' => $isFinished ? ($goalsHome > $goalsAway) : null,

            // Away team
            'away_team_id' => $awayTeam['id'],
            'away_team_name' => $awayTeam['name'],
            'away_team_logo' => $awayTeam['logo'],
            'away_team_winner' => $isFinished ? ($goalsAway > $goalsHome) : null,

            // Scores
            'goals_home' => $goalsHome,
            'goals_away' => $goalsAway,
            'halftime_home' => $isFinished ? $this->faker->numberBetween(0, $goalsHome) : null,
            'halftime_away' => $isFinished ? $this->faker->numberBetween(0, $goalsAway) : null,
            'fulltime_home' => $goalsHome,
            'fulltime_away' => $goalsAway,

            // Additional data
            'odds' => null,
            'has_odds' => false,
            'head2head' => null,
            'statistics' => null,
        ];
    }

    /**
     * Create a finished fixture.
     */
    public function finished(): static
    {
        return $this->state(function (array $attributes) {
            $goalsHome = $this->faker->numberBetween(0, 5);
            $goalsAway = $this->faker->numberBetween(0, 5);

            return [
                'status_long' => 'Match Finished',
                'status_short' => 'FT',
                'elapsed' => 90,
                'goals_home' => $goalsHome,
                'goals_away' => $goalsAway,
                'halftime_home' => $this->faker->numberBetween(0, $goalsHome),
                'halftime_away' => $this->faker->numberBetween(0, $goalsAway),
                'fulltime_home' => $goalsHome,
                'fulltime_away' => $goalsAway,
                'home_team_winner' => $goalsHome > $goalsAway,
                'away_team_winner' => $goalsAway > $goalsHome,
            ];
        });
    }

    /**
     * Create an upcoming fixture.
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('+1 day', '+30 days');

            return [
                'date' => $date,
                'timestamp' => $date->getTimestamp(),
                'status_long' => 'Not Started',
                'status_short' => 'NS',
                'elapsed' => null,
                'goals_home' => null,
                'goals_away' => null,
                'halftime_home' => null,
                'halftime_away' => null,
                'fulltime_home' => null,
                'fulltime_away' => null,
                'home_team_winner' => null,
                'away_team_winner' => null,
            ];
        });
    }

    /**
     * Create a fixture with odds.
     */
    public function withOdds(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_odds' => true,
            'odds' => [
                [
                    'name' => 'Match Winner',
                    'values' => [
                        ['value' => 'Home', 'odd' => $this->faker->randomFloat(2, 1.5, 5.0)],
                        ['value' => 'Draw', 'odd' => $this->faker->randomFloat(2, 2.5, 4.0)],
                        ['value' => 'Away', 'odd' => $this->faker->randomFloat(2, 1.5, 5.0)],
                    ],
                ],
            ],
        ]);
    }
}
