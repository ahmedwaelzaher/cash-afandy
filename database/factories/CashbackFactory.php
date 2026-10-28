<?php

namespace Database\Factories;

use App\Models\Cashback;
use App\Models\Country;
use App\Models\Representative;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Cashback>
 */
class CashbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'percentage' => $this->faker->randomFloat(2, 0, 100),
            'details' => [
                ['category' => $this->faker->word(), 'value' => $this->faker->randomFloat(2, 0, 100)],
                ['category' => $this->faker->word(), 'value' => $this->faker->randomFloat(2, 0, 100)],
            ],
            'store_id' => Store::factory(),
            'representative_id' => Representative::factory(),
            'country_id' => fn () => Arr::random(Country::pluck('id')->all()),
            'terms' => ['en' => $this->faker->sentence()],
            'how_it_works' => ['en' => $this->faker->sentence()],
            'verification_period' => $this->faker->optional()->numberBetween(1, 90),
            'tips' => ['en' => $this->faker->sentence()],
            'launch_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('+2 months', '+3 months'),
            'active' => $this->faker->boolean(75),
        ];
    }
}
