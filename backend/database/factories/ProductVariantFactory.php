<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Standard',
            'sku' => fake()->unique()->bothify('VAR-########'),
            'price' => fake()->randomFloat(2, 25, 1000),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
