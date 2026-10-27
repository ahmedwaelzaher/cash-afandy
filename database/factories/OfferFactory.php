<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Offer;
use App\Models\Representative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->sentence()],
            'code' => strtoupper($this->faker->unique()->bothify('??###')),
            'client_id' => Client::factory(),
            'representative_id' => Representative::factory(),
            'description' => ['en' => $this->faker->sentence()],
            'tips' => ['en' => $this->faker->sentence()],
            'fixed_discount' => $this->faker->boolean(),
            'discount' => $this->faker->randomFloat(2, 0, 100),
            'minimum_amount' => $this->faker->optional()->randomFloat(2, 0, 50),
            'maximum_amount' => $this->faker->optional()->randomFloat(2, 51, 100),
            'minimum_usages' => $this->faker->optional()->numberBetween(1, 10),
            'maximum_usages' => $this->faker->optional()->numberBetween(11, 1000),
            'launch_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('+2 months', '+3 months'),
            'active' => $this->faker->boolean(75),
        ];
    }
}
