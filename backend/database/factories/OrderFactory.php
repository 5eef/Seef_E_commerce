<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => fake()->unique()->bothify('SEEF-########'),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'shipping_total' => '0.00',
            'tax_total' => '0.00',
            'grand_total' => '100.00',
            'shipping_address' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->phoneNumber(),
                'address_line_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'MA',
            ],
            'placed_at' => now(),
        ];
    }
}
