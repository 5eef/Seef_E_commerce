<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Veste en lin naturel', 'slug' => 'veste-lin-naturel', 'sku' => 'SEEF-VLN',
                'base_price' => '189.00', 'category' => 'vetements', 'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1603400521630-9f2de124b33b?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Taille', 'variants' => [['S', 12], ['M', 18], ['L', 7]],
            ],
            [
                'name' => 'Pull laine mérinos', 'slug' => 'pull-laine-merinos', 'sku' => 'SEEF-PLM',
                'base_price' => '195.00', 'sale_price' => '145.00', 'category' => 'vetements', 'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1621198059871-0d5f9b449233?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Taille', 'variants' => [['S', 9], ['M', 4], ['L', 0]],
            ],
            [
                'name' => 'Bottines cuir brun', 'slug' => 'bottines-cuir-brun', 'sku' => 'SEEF-BCB',
                'base_price' => '265.00', 'category' => 'chaussures', 'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1479064555552-3ef4979f8908?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Pointure', 'variants' => [['40', 8], ['41', 3], ['42', 0]],
            ],
            [
                'name' => 'Sac cuir souple', 'slug' => 'sac-cuir-souple', 'sku' => 'SEEF-SCS',
                'base_price' => '320.00', 'category' => 'accessoires', 'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1589363460779-cd717d2ed8fa?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 14]],
            ],
            [
                'name' => 'Sneakers Horizon', 'slug' => 'sneakers-horizon', 'sku' => 'SEEF-SNH',
                'base_price' => '229.00', 'sale_price' => '199.00', 'category' => 'chaussures',
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Pointure', 'variants' => [['39', 11], ['40', 16], ['41', 6], ['42', 2]],
            ],
            [
                'name' => 'Chemise popeline blanche', 'slug' => 'chemise-popeline-blanche', 'sku' => 'SEEF-CPB',
                'base_price' => '139.00', 'category' => 'vetements',
                'image' => 'https://images.unsplash.com/photo-1603252109303-2751441dd157?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Taille', 'variants' => [['S', 10], ['M', 15], ['L', 8], ['XL', 3]],
            ],
            [
                'name' => 'Robe fluide Sable', 'slug' => 'robe-fluide-sable', 'sku' => 'SEEF-RFS',
                'base_price' => '215.00', 'category' => 'nouveautes',
                'image' => 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&h=1125&fit=crop&auto=format',
                'option' => 'Taille', 'variants' => [['XS', 5], ['S', 9], ['M', 7], ['L', 1]],
            ],
            [
                'name' => 'Montre minimaliste Atlas', 'slug' => 'montre-minimaliste-atlas', 'sku' => 'SEEF-MMA',
                'base_price' => '349.00', 'category' => 'accessoires',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 6]],
            ],
            [
                'name' => 'Lunettes Riviera', 'slug' => 'lunettes-riviera', 'sku' => 'SEEF-LRV',
                'base_price' => '119.00', 'sale_price' => '89.00', 'category' => 'accessoires',
                'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 20]],
            ],
            [
                'name' => 'Casquette coton Horizon', 'slug' => 'casquette-coton-horizon', 'sku' => 'SEEF-CCH',
                'base_price' => '69.00', 'category' => 'nouveautes',
                'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 18]],
            ],
            [
                'name' => 'Écharpe tissée Azur', 'slug' => 'echarpe-tissee-azur', 'sku' => 'SEEF-ETA',
                'base_price' => '79.00', 'category' => 'accessoires',
                'image' => 'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 0]],
            ],
            [
                'name' => 'Sac à dos Urbain', 'slug' => 'sac-a-dos-urbain', 'sku' => 'SEEF-SDU',
                'base_price' => '179.00', 'category' => 'nouveautes',
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&h=1125&fit=crop&auto=format',
                'variants' => [['Standard', 5]],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::query()->withTrashed()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'short_description' => 'Une pièce Seef contemporaine, pensée pour un usage quotidien.',
                    'description' => 'Conception soignée, coupe épurée et finitions durables. Produit de démonstration destiné à tester le catalogue, le panier, la wishlist et le checkout.',
                    'base_price' => $data['base_price'],
                    'sale_price' => $data['sale_price'] ?? null,
                    'status' => 'active',
                    'is_featured' => $data['featured'] ?? false,
                    'published_at' => now(),
                ]
            );
            if ($product->trashed()) {
                $product->restore();
            }

            $category = Category::query()->where('slug', $data['category'])->firstOrFail();
            $product->categories()->syncWithoutDetaching([$category->id]);
            $product->images()->updateOrCreate(
                ['sort_order' => 0],
                ['disk' => 'external', 'path' => $data['image'], 'alt_text' => $data['name'], 'is_primary' => true]
            );

            $option = isset($data['option'])
                ? $product->options()->updateOrCreate(['name' => $data['option']], ['sort_order' => 0])
                : null;
            $activeSkus = [];

            foreach ($data['variants'] as $index => [$value, $stock]) {
                $sku = $data['sku'].'-'.strtoupper($value === 'Standard' ? 'STD' : $value);
                $activeSkus[] = $sku;
                $variant = $product->variants()->withTrashed()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $value,
                        'price' => $data['base_price'],
                        'sale_price' => $data['sale_price'] ?? null,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
                if ($variant->trashed()) {
                    $variant->restore();
                }
                $variant->inventory()->updateOrCreate([], [
                    'on_hand_quantity' => $stock,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);

                if ($option !== null) {
                    $optionValue = $option->values()->updateOrCreate(
                        ['value' => $value],
                        ['sort_order' => $index]
                    );
                    $variant->optionValues()->sync([$optionValue->id]);
                } else {
                    $variant->optionValues()->detach();
                }
            }

            $product->variants()->whereNotIn('sku', $activeSkus)->update(['is_active' => false]);
        }
    }
}
