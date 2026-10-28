<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => [
                'en' => $this->faker->company(),
            ],
            'slug' => $this->faker->unique()->slug(),
            'description' => [
                'en' => $this->faker->sentence(),
            ],
            'url' => $this->faker->url(),
            'logo' => $this->faker->imageUrl(),
            'active' => true,
        ];
    }
}
