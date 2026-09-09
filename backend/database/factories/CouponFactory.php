<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SAVE##??'),
            'name' => fake()->words(2, true),
            'type' => 'percentage',
            'value' => '10.00',
            'minimum_order_amount' => '100.00',
            'usage_limit' => 100,
            'usage_limit_per_user' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
