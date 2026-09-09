<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function create(
        Product $product,
        array $data
    ): ProductVariant {
        return DB::transaction(
            function () use (
                $product,
                $data
            ): ProductVariant {
                $optionValueIds = $data[
                    'option_value_ids'
                ] ?? [];

                unset(
                    $data['option_value_ids']
                );

                $variant = $product
                    ->variants()
                    ->create($data);

                $variant
                    ->optionValues()
                    ->sync(
                        $optionValueIds
                    );

                /*
                 * Toute variante possède son inventaire.
                 * Les quantités seront gérées dans
                 * le module Inventaire.
                 */
                $variant
                    ->inventory()
                    ->create([
                        'on_hand_quantity' => 0,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 5,
                    ]);

                return $this->loadForAdmin(
                    $variant
                );
            }
        );
    }

    public function update(
        ProductVariant $variant,
        array $data
    ): ProductVariant {
        return DB::transaction(
            function () use (
                $variant,
                $data
            ): ProductVariant {
                $syncOptionValues = array_key_exists(
                    'option_value_ids',
                    $data
                );

                $optionValueIds = $data[
                    'option_value_ids'
                ] ?? [];

                unset(
                    $data['option_value_ids']
                );

                $variant->update(
                    $data
                );

                if ($syncOptionValues) {
                    $variant
                        ->optionValues()
                        ->sync(
                            $optionValueIds
                        );
                }

                $variant->refresh();

                return $this->loadForAdmin(
                    $variant
                );
            }
        );
    }

    public function delete(
        ProductVariant $variant
    ): void {
        DB::transaction(
            function () use ($variant): void {
                $variant->delete();
            }
        );
    }

    public function restore(
        Product $product,
        int $variantId
    ): ProductVariant {
        return DB::transaction(
            function () use (
                $product,
                $variantId
            ): ProductVariant {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::query()
                    ->onlyTrashed()
                    ->where(
                        'product_id',
                        $product->id
                    )
                    ->findOrFail(
                        $variantId
                    );

                $variant->restore();

                $variant->refresh();

                return $this->loadForAdmin(
                    $variant
                );
            }
        );
    }

    public function loadForAdmin(
        ProductVariant $variant
    ): ProductVariant {
        return $variant
            ->load([
                'optionValues' => fn ($relation) => $relation
                    ->orderBy(
                        'product_option_values.product_option_id'
                    )
                    ->orderBy(
                        'product_option_values.sort_order'
                    )
                    ->orderBy(
                        'product_option_values.id'
                    ),

                'inventory',
            ])
            ->loadCount([
                'cartItems',
                'orderItems',
            ]);
    }
}
