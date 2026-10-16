<?php

namespace Database\Factories;

use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<StaticPage>
 */
class StaticPageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = StaticPage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = [
            'title' => [],
            'content' => [],
            'active' => true,
        ];

        foreach (array_keys(config('app.locales')) as $locale) {
            $faker = fake_locale($locale);

            $data['title'][$locale] = $faker->realText(40);
            $data['content'][$locale] = $faker->realText(500);
        }

        return $data;
    }
}
