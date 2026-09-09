<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Seef Admin', 'email' => 'admin@seef.test', 'role' => 'admin'],
            ['name' => 'Client Démo', 'email' => 'client@seef.test', 'role' => 'customer'],
        ] as $data) {
            $user = User::query()->firstOrNew(['email' => $data['email']]);
            $user->forceFill($data + [
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->save();
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
