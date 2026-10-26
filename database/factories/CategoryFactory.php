<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
                'en' => $this->faker->words(2, true),
            ],
            'slug' => $this->faker->unique()->slug(),
            'description' => [
                'en' => $this->faker->sentence(),
            ],
            'icon' => 'ti ti-category',
        ];
    }
}
