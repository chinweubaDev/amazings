<?php

namespace Database\Factories;

use App\Models\Odd;
use App\Models\Fixture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Odd>
 */
class OddFactory extends Factory
{
    protected $model = Odd::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookmakers = [
            ['id' => 1, 'name' => 'Bet365'],
            ['id' => 2, 'name' => 'Betway'],
            ['id' => 3, 'name' => '1xBet'],
            ['id' => 4, 'name' => 'William Hill'],
            ['id' => 5, 'name' => 'Unibet'],
            ['id' => 6, 'name' => 'Bwin'],
        ];

        $betTypes = [
            ['name' => 'Match Winner', 'values' => ['Home', 'Draw', 'Away']],
            ['name' => 'Over/Under 2.5', 'values' => ['Over 2.5', 'Under 2.5']],
            ['name' => 'Both Teams Score', 'values' => ['Yes', 'No']],
            ['name' => 'Double Chance', 'values' => ['Home/Draw', 'Home/Away', 'Draw/Away']],
        ];

        $bookmaker = $this->faker->randomElement($bookmakers);
        $betType = $this->faker->randomElement($betTypes);
        $betValue = $this->faker->randomElement($betType['values']);

        return [
            'fixture_id' => $this->faker->numberBetween(100000, 999999),
            'bookmaker_id' => $bookmaker['id'],
            'bookmaker_name' => $bookmaker['name'],
            'bet_name' => $betType['name'],
            'bet_value' => $betValue,
            'odd' => $this->faker->randomFloat(2, 1.01, 10.00),
        ];
    }

    /**
     * Create odds for a specific fixture.
     */
    public function forFixture(int $fixtureId): static
    {
        return $this->state(fn (array $attributes) => [
            'fixture_id' => $fixtureId,
        ]);
    }

    /**
     * Create match winner odds.
     */
    public function matchWinner(): static
    {
        return $this->state(function (array $attributes) {
            $values = ['Home', 'Draw', 'Away'];
            return [
                'bet_name' => 'Match Winner',
                'bet_value' => $this->faker->randomElement($values),
            ];
        });
    }
}
