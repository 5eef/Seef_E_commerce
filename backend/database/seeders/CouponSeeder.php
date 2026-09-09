<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->updateOrCreate(
            ['code' => 'BIENVENUE10'],
            [
                'name' => 'Bienvenue',
                'description' => 'Remise de démonstration pour une première commande.',
                'type' => 'percentage',
                'value' => '10.00',
                'minimum_order_amount' => '100.00',
                'maximum_discount_amount' => '150.00',
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'is_active' => true,
            ]
        );
    }
}
