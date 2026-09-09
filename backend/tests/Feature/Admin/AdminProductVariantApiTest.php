<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductVariantApiTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_cannot_access_product_variants(): void
    {
        $product = $this->createProduct();

        $this->getJson(
            "/api/admin/products/{$product->id}/variants"
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_product_variants(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $product = $this->createProduct();

        $this->getJson(
            "/api/admin/products/{$product->id}/variants"
        )->assertForbidden();
    }

    public function test_admin_can_create_variant_with_option_values_and_inventory(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 500,
        ]);

        $color = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $size = $this->createOption(
            $product,
            [
                'name' => 'Size',
            ]
        );

        $black = $this->createValue(
            $color,
            [
                'value' => 'Black',
            ]
        );

        $large = $this->createValue(
            $size,
            [
                'value' => 'L',
            ]
        );

        $response = $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'name' => 'Black / L',
                'sku' => 'phone-black-l',
                'barcode' => '1234567890123',

                'price' => 480,
                'sale_price' => 450,
                'cost_price' => 300,

                'weight_grams' => 220,

                'length_cm' => 16.5,
                'width_cm' => 7.5,
                'height_cm' => 0.8,

                'is_active' => true,
                'sort_order' => 1,

                'option_value_ids' => [
                    $black->id,
                    $large->id,
                ],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.product_id',
                $product->id
            )
            ->assertJsonPath(
                'data.name',
                'Black / L'
            )
            ->assertJsonPath(
                'data.sku',
                'PHONE-BLACK-L'
            )
            ->assertJsonPath(
                'data.barcode',
                '1234567890123'
            )
            ->assertJsonPath(
                'data.price',
                '480.00'
            )
            ->assertJsonPath(
                'data.sale_price',
                '450.00'
            )
            ->assertJsonPath(
                'data.cost_price',
                '300.00'
            )
            ->assertJsonCount(
                2,
                'data.option_values'
            )
            ->assertJsonPath(
                'data.inventory.on_hand_quantity',
                0
            )
            ->assertJsonPath(
                'data.inventory.reserved_quantity',
                0
            )
            ->assertJsonPath(
                'data.inventory.available_quantity',
                0
            )
            ->assertJsonPath(
                'data.inventory.low_stock_threshold',
                5
            )
            ->assertJsonPath(
                'data.inventory.is_low_stock',
                true
            );

        $variant = ProductVariant::query()
            ->where(
                'sku',
                'PHONE-BLACK-L'
            )
            ->firstOrFail();

        $this->assertDatabaseHas(
            'inventories',
            [
                'product_variant_id' => $variant->id,
                'on_hand_quantity' => 0,
                'reserved_quantity' => 0,
                'low_stock_threshold' => 5,
            ]
        );

        $this->assertDatabaseHas(
            'product_option_value_product_variant',
            [
                'product_variant_id' => $variant->id,
                'product_option_value_id' => $black->id,
            ]
        );

        $this->assertDatabaseHas(
            'product_option_value_product_variant',
            [
                'product_variant_id' => $variant->id,
                'product_option_value_id' => $large->id,
            ]
        );
    }

    public function test_client_cannot_control_inventory_when_creating_variant(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'inventory-hack',
                'on_hand_quantity' => 999999,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'on_hand_quantity'
            );

        $this->assertDatabaseMissing(
            'product_variants',
            [
                'sku' => 'INVENTORY-HACK',
            ]
        );
    }

    public function test_variant_rejects_option_value_from_another_product(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $foreignOption = $this->createOption(
            $secondProduct
        );

        $foreignValue = $this->createValue(
            $foreignOption
        );

        $this->postJson(
            "/api/admin/products/{$firstProduct->id}/variants",
            [
                'sku' => 'foreign-option-value',
                'option_value_ids' => [
                    $foreignValue->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'option_value_ids'
            );
    }

    public function test_variant_cannot_have_two_values_from_same_option(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $color = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $black = $this->createValue(
            $color,
            [
                'value' => 'Black',
            ]
        );

        $white = $this->createValue(
            $color,
            [
                'value' => 'White',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'invalid-colors',
                'option_value_ids' => [
                    $black->id,
                    $white->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'option_value_ids'
            );
    }

    public function test_variant_sale_price_cannot_exceed_effective_regular_price(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 500,
        ]);

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'bad-sale-parent',
                'sale_price' => 600,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'bad-sale-variant',
                'price' => 300,
                'sale_price' => 400,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );
    }

    public function test_sku_and_barcode_are_unique_and_update_ignores_current_variant(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $variant = $this->createVariant(
            $product,
            [
                'sku' => 'VARIANT-001',
                'barcode' => 'BARCODE-001',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'variant-001',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sku'
            );

        $this->postJson(
            "/api/admin/products/{$product->id}/variants",
            [
                'sku' => 'VARIANT-002',
                'barcode' => 'BARCODE-001',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'barcode'
            );

        $this->patchJson(
            "/api/admin/products/{$product->id}/variants/{$variant->id}",
            [
                'sku' => 'variant-001',
                'barcode' => 'BARCODE-001',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.sku',
                'VARIANT-001'
            )
            ->assertJsonPath(
                'data.barcode',
                'BARCODE-001'
            );
    }

    public function test_admin_can_list_filter_search_sort_and_paginate_variants(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 500,
        ]);

        $cheap = $this->createVariant(
            $product,
            [
                'name' => 'Gaming Cheap',
                'sku' => 'GAMING-CHEAP',
                'price' => 100,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $this->createVariant(
            $product,
            [
                'name' => 'Gaming Expensive',
                'sku' => 'GAMING-EXPENSIVE',
                'price' => 900,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $this->createVariant(
            $product,
            [
                'name' => 'Disabled Gaming',
                'sku' => 'DISABLED-GAMING',
                'price' => 50,
                'is_active' => false,
                'sort_order' => 1,
            ]
        );

        $response = $this->getJson(
            "/api/admin/products/{$product->id}/variants?q=gaming&active=true&sort=price_asc&per_page=2"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $cheap->id
            )
            ->assertJsonPath(
                'meta.per_page',
                2
            );
    }

    public function test_invalid_variant_filters_are_rejected(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $this->getJson(
            "/api/admin/products/{$product->id}/variants?trashed=invalid"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'trashed'
            );

        $this->getJson(
            "/api/admin/products/{$product->id}/variants?sort=invalid"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sort'
            );

        $this->getJson(
            "/api/admin/products/{$product->id}/variants?per_page=500"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'per_page'
            );
    }

    public function test_admin_can_update_variant_and_sync_option_values(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 700,
        ]);

        $color = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $size = $this->createOption(
            $product,
            [
                'name' => 'Size',
            ]
        );

        $black = $this->createValue(
            $color,
            [
                'value' => 'Black',
            ]
        );

        $white = $this->createValue(
            $color,
            [
                'value' => 'White',
            ]
        );

        $large = $this->createValue(
            $size,
            [
                'value' => 'L',
            ]
        );

        $variant = $this->createVariant(
            $product,
            [
                'name' => 'Black',
                'sku' => 'SYNC-001',
                'price' => 650,
            ]
        );

        $variant
            ->optionValues()
            ->attach(
                $black->id
            );

        $this->patchJson(
            "/api/admin/products/{$product->id}/variants/{$variant->id}",
            [
                'name' => 'White / L',
                'sku' => 'sync-001-updated',
                'price' => 600,
                'sale_price' => 550,
                'option_value_ids' => [
                    $white->id,
                    $large->id,
                ],
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'White / L'
            )
            ->assertJsonPath(
                'data.sku',
                'SYNC-001-UPDATED'
            )
            ->assertJsonPath(
                'data.price',
                '600.00'
            )
            ->assertJsonCount(
                2,
                'data.option_values'
            );

        $this->assertDatabaseMissing(
            'product_option_value_product_variant',
            [
                'product_variant_id' => $variant->id,
                'product_option_value_id' => $black->id,
            ]
        );

        $this->assertDatabaseHas(
            'product_option_value_product_variant',
            [
                'product_variant_id' => $variant->id,
                'product_option_value_id' => $white->id,
            ]
        );

        $this->assertDatabaseHas(
            'product_option_value_product_variant',
            [
                'product_variant_id' => $variant->id,
                'product_option_value_id' => $large->id,
            ]
        );
    }

    public function test_update_validates_sale_price_against_existing_or_new_regular_price(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 500,
        ]);

        $variant = $this->createVariant(
            $product,
            [
                'sku' => 'PRICE-UPDATE',
                'price' => 400,
                'sale_price' => 350,
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/variants/{$variant->id}",
            [
                'sale_price' => 450,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );

        $this->patchJson(
            "/api/admin/products/{$product->id}/variants/{$variant->id}",
            [
                'price' => 300,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );
    }

    public function test_variant_from_another_product_cannot_be_accessed_through_product(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $foreignVariant = $this->createVariant(
            $secondProduct
        );

        $this->getJson(
            "/api/admin/products/{$firstProduct->id}/variants/{$foreignVariant->id}"
        )->assertNotFound();

        $this->patchJson(
            "/api/admin/products/{$firstProduct->id}/variants/{$foreignVariant->id}",
            [
                'name' => 'Illegal update',
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/admin/products/{$firstProduct->id}/variants/{$foreignVariant->id}"
        )->assertNotFound();
    }

    public function test_admin_can_soft_delete_variant_and_list_only_deleted_variants(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $active = $this->createVariant(
            $product,
            [
                'sku' => 'ACTIVE-VARIANT',
            ]
        );

        $deleted = $this->createVariant(
            $product,
            [
                'sku' => 'DELETED-VARIANT',
            ]
        );

        $deleted
            ->inventory()
            ->create([
                'on_hand_quantity' => 10,
                'reserved_quantity' => 2,
                'low_stock_threshold' => 3,
            ]);

        $this->deleteJson(
            "/api/admin/products/{$product->id}/variants/{$deleted->id}"
        )->assertNoContent();

        $this->assertSoftDeleted(
            'product_variants',
            [
                'id' => $deleted->id,
            ]
        );

        /*
         * Le soft-delete ne doit pas supprimer
         * l'inventaire historique.
         */
        $this->assertDatabaseHas(
            'inventories',
            [
                'product_variant_id' => $deleted->id,
                'on_hand_quantity' => 10,
            ]
        );

        $this->getJson(
            "/api/admin/products/{$product->id}/variants/{$deleted->id}"
        )->assertNotFound();

        $response = $this->getJson(
            "/api/admin/products/{$product->id}/variants?trashed=only"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $deleted->id
            );

        $this->assertNotSame(
            $active->id,
            $response->json(
                'data.0.id'
            )
        );
    }

    public function test_admin_can_restore_variant_and_preserve_inventory_and_option_values(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $value = $this->createValue(
            $option,
            [
                'value' => 'Black',
            ]
        );

        $variant = $this->createVariant(
            $product,
            [
                'sku' => 'RESTORE-VARIANT',
            ]
        );

        $variant
            ->optionValues()
            ->attach(
                $value->id
            );

        $variant
            ->inventory()
            ->create([
                'on_hand_quantity' => 15,
                'reserved_quantity' => 4,
                'low_stock_threshold' => 3,
            ]);

        $variant->delete();

        $this->postJson(
            "/api/admin/products/{$product->id}/variants/{$variant->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $variant->id
            )
            ->assertJsonPath(
                'data.deleted_at',
                null
            )
            ->assertJsonPath(
                'data.inventory.on_hand_quantity',
                15
            )
            ->assertJsonPath(
                'data.inventory.reserved_quantity',
                4
            )
            ->assertJsonPath(
                'data.inventory.available_quantity',
                11
            )
            ->assertJsonCount(
                1,
                'data.option_values'
            )
            ->assertJsonPath(
                'data.option_values.0.id',
                $value->id
            );

        $this->assertDatabaseHas(
            'product_variants',
            [
                'id' => $variant->id,
                'deleted_at' => null,
            ]
        );
    }

    public function test_deleted_variant_cannot_be_restored_through_another_product(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $variant = $this->createVariant(
            $secondProduct,
            [
                'sku' => 'FOREIGN-RESTORE',
            ]
        );

        $variant->delete();

        $this->postJson(
            "/api/admin/products/{$firstProduct->id}/variants/{$variant->id}/restore"
        )->assertNotFound();

        $this->assertSoftDeleted(
            'product_variants',
            [
                'id' => $variant->id,
            ]
        );
    }

    private function actingAsAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs(
            $admin,
            'web'
        );

        return $admin;
    }

    private function createProduct(
        array $attributes = []
    ): Product {
        $sequence = ++$this->sequence;

        return Product::query()->create(
            array_merge(
                [
                    'name' => "Product {$sequence}",
                    'slug' => "product-{$sequence}",
                    'sku' => "PRODUCT-{$sequence}",
                    'base_price' => 500,
                    'status' => 'draft',
                    'is_featured' => false,
                ],
                $attributes
            )
        );
    }

    private function createOption(
        Product $product,
        array $attributes = []
    ): ProductOption {
        $sequence = ++$this->sequence;

        return $product
            ->options()
            ->create(
                array_merge(
                    [
                        'name' => "Option {$sequence}",
                        'sort_order' => 0,
                    ],
                    $attributes
                )
            );
    }

    private function createValue(
        ProductOption $option,
        array $attributes = []
    ): ProductOptionValue {
        $sequence = ++$this->sequence;

        return $option
            ->values()
            ->create(
                array_merge(
                    [
                        'value' => "Value {$sequence}",
                        'metadata' => null,
                        'sort_order' => 0,
                    ],
                    $attributes
                )
            );
    }

    private function createVariant(
        Product $product,
        array $attributes = []
    ): ProductVariant {
        $sequence = ++$this->sequence;

        return $product
            ->variants()
            ->create(
                array_merge(
                    [
                        'name' => "Variant {$sequence}",
                        'sku' => "VARIANT-{$sequence}",
                        'price' => null,
                        'sale_price' => null,
                        'cost_price' => null,
                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                    $attributes
                )
            );
    }
}
