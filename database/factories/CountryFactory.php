<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = [
            ['name' => 'England', 'code' => 'GB', 'flag' => 'https://media.api-sports.io/flags/gb.svg'],
            ['name' => 'Spain', 'code' => 'ES', 'flag' => 'https://media.api-sports.io/flags/es.svg'],
            ['name' => 'Germany', 'code' => 'DE', 'flag' => 'https://media.api-sports.io/flags/de.svg'],
            ['name' => 'Italy', 'code' => 'IT', 'flag' => 'https://media.api-sports.io/flags/it.svg'],
            ['name' => 'France', 'code' => 'FR', 'flag' => 'https://media.api-sports.io/flags/fr.svg'],
            ['name' => 'Portugal', 'code' => 'PT', 'flag' => 'https://media.api-sports.io/flags/pt.svg'],
            ['name' => 'Netherlands', 'code' => 'NL', 'flag' => 'https://media.api-sports.io/flags/nl.svg'],
            ['name' => 'Belgium', 'code' => 'BE', 'flag' => 'https://media.api-sports.io/flags/be.svg'],
        ];

        $country = $this->faker->randomElement($countries);

        return [
            'name' => $country['name'],
            'code' => $country['code'],
            'flag' => $country['flag'],
        ];
    }

    /**
     * Create a specific country.
     */
    public function country(string $name, string $code, string $flag): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'code' => $code,
            'flag' => $flag,
        ]);
    }
}
