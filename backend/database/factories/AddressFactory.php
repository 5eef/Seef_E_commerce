<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'MA',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }
}
