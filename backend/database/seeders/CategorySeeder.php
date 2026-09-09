<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Vêtements', 'slug' => 'vetements', 'sort_order' => 1],
            ['name' => 'Chaussures', 'slug' => 'chaussures', 'sort_order' => 2],
            ['name' => 'Accessoires', 'slug' => 'accessoires', 'sort_order' => 3],
            ['name' => 'Nouveautés', 'slug' => 'nouveautes', 'sort_order' => 4],
        ] as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }
    }
}
