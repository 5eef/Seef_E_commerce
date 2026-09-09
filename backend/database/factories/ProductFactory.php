<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->bothify('PRD-#####'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 25, 1000),
            'status' => 'active',
            'is_featured' => false,
            'published_at' => now()->subDay(),
        ];
    }
}
