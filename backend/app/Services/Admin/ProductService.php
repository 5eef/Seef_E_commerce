<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(
        array $data
    ): Product {
        return DB::transaction(
            function () use ($data): Product {
                $categoryIds = $data[
                    'category_ids'
                ] ?? [];

                unset(
                    $data['category_ids']
                );

                $product = Product::query()
                    ->create($data);

                $product
                    ->categories()
                    ->sync($categoryIds);

                return $this->loadForAdmin(
                    $product
                );
            }
        );
    }

    public function update(
        Product $product,
        array $data
    ): Product {
        return DB::transaction(
            function () use (
                $product,
                $data
            ): Product {
                $hasCategories = array_key_exists(
                    'category_ids',
                    $data
                );

                $categoryIds = $data[
                    'category_ids'
                ] ?? [];

                unset(
                    $data['category_ids']
                );

                if ($data !== []) {
                    $product->update($data);
                }

                if ($hasCategories) {
                    $product
                        ->categories()
                        ->sync($categoryIds);
                }

                $product->refresh();

                return $this->loadForAdmin(
                    $product
                );
            }
        );
    }

    public function delete(
        Product $product
    ): void {
        DB::transaction(
            function () use ($product): void {
                /*
                 * Soft delete uniquement.
                 *
                 * Les commandes historiques gardent
                 * leurs références au produit.
                 */
                $product->delete();
            }
        );
    }

    public function restore(
        int $productId
    ): Product {
        return DB::transaction(
            function () use (
                $productId
            ): Product {
                /** @var Product $product */
                $product = Product::query()
                    ->onlyTrashed()
                    ->findOrFail(
                        $productId
                    );

                $product->restore();

                $product->refresh();

                return $this->loadForAdmin(
                    $product
                );
            }
        );
    }

    public function loadForAdmin(
        Product $product
    ): Product {
        return $product
            ->load([
                'categories' => fn (
                    $relation
                ) => $relation
                    ->orderBy(
                        'categories.sort_order'
                    )
                    ->orderBy(
                        'categories.name'
                    ),
            ])
            ->loadCount([
                'variants',
                'images',
                'reviews',
                'orderItems',
            ]);
    }
}
