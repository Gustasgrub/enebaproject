<?php

namespace Database\Factories;

use App\Enums\Platform;
use App\Enums\Region;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Test data only. The real catalog comes from DatabaseSeeder, which does not use Faker because
 * production installs no dev dependencies.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->words(3, true));

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'name' => $name,
            'description' => fake()->sentence(),
            'image_path' => 'covers/'.Str::slug($name).'.svg',
            'platform' => fake()->randomElement(Platform::cases()),
            'region' => fake()->randomElement(Region::cases()),
            'genre' => fake()->randomElement(['Action', 'RPG', 'Shooter', 'Strategy']),
            'release_year' => fake()->numberBetween(2010, 2025),
            'search_keywords' => '',
        ];
    }
}
