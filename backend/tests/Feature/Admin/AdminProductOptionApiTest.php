<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductOptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_options(): void
    {
        $product = $this->createProduct();

        $this->getJson(
            "/api/admin/products/{$product->id}/options"
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_product_options(): void
    {
        /** @var User $customer
         */
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
            "/api/admin/products/{$product->id}/options"
        )->assertForbidden();
    }

    public function test_admin_can_create_product_option(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $this->postJson(
            "/api/admin/products/{$product->id}/options",
            [
                'name' => 'Color',
                'sort_order' => 1,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.product_id',
                $product->id
            )
            ->assertJsonPath(
                'data.name',
                'Color'
            )
            ->assertJsonPath(
                'data.sort_order',
                1
            )
            ->assertJsonCount(
                0,
                'data.values'
            );

        $this->assertDatabaseHas(
            'product_options',
            [
                'product_id' => $product->id,
                'name' => 'Color',
                'sort_order' => 1,
            ]
        );
    }

    public function test_admin_can_create_option_value_with_metadata(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$product->id}/options/{$option->id}/values",
            [
                'value' => 'Black',
                'metadata' => [
                    'hex' => '#000000',
                ],
                'sort_order' => 1,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.product_option_id',
                $option->id
            )
            ->assertJsonPath(
                'data.value',
                'Black'
            )
            ->assertJsonPath(
                'data.metadata.hex',
                '#000000'
            )
            ->assertJsonPath(
                'data.sort_order',
                1
            );

        $this->assertDatabaseHas(
            'product_option_values',
            [
                'product_option_id' => $option->id,
                'value' => 'Black',
                'sort_order' => 1,
            ]
        );
    }

    public function test_admin_can_list_options_with_sorted_values(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $size = $this->createOption(
            $product,
            [
                'name' => 'Size',
                'sort_order' => 2,
            ]
        );

        $color = $this->createOption(
            $product,
            [
                'name' => 'Color',
                'sort_order' => 1,
            ]
        );

        $this->createValue(
            $color,
            [
                'value' => 'White',
                'sort_order' => 2,
            ]
        );

        $black = $this->createValue(
            $color,
            [
                'value' => 'Black',
                'sort_order' => 1,
            ]
        );

        $response = $this->getJson(
            "/api/admin/products/{$product->id}/options"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $color->id
            )
            ->assertJsonPath(
                'data.0.values.0.id',
                $black->id
            )
            ->assertJsonPath(
                'data.1.id',
                $size->id
            );
    }

    public function test_admin_can_show_product_option(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product,
            [
                'name' => 'Storage',
            ]
        );

        $value = $this->createValue(
            $option,
            [
                'value' => '256 GB',
            ]
        );

        $this->getJson(
            "/api/admin/products/{$product->id}/options/{$option->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $option->id
            )
            ->assertJsonPath(
                'data.name',
                'Storage'
            )
            ->assertJsonPath(
                'data.values.0.id',
                $value->id
            );
    }

    public function test_admin_can_update_option_and_option_value(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product,
            [
                'name' => 'Colour',
            ]
        );

        $value = $this->createValue(
            $option,
            [
                'value' => 'Dark',
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/options/{$option->id}",
            [
                'name' => 'Color',
                'sort_order' => 3,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Color'
            )
            ->assertJsonPath(
                'data.sort_order',
                3
            );

        $this->patchJson(
            "/api/admin/products/{$product->id}/options/{$option->id}/values/{$value->id}",
            [
                'value' => 'Black',
                'metadata' => [
                    'hex' => '#000000',
                ],
                'sort_order' => 2,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.value',
                'Black'
            )
            ->assertJsonPath(
                'data.metadata.hex',
                '#000000'
            )
            ->assertJsonPath(
                'data.sort_order',
                2
            );
    }

    public function test_duplicate_option_name_is_rejected_for_same_product(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$product->id}/options",
            [
                'name' => 'Color',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'name'
            );
    }

    public function test_same_option_name_can_exist_on_different_products(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $this->createOption(
            $firstProduct,
            [
                'name' => 'Color',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$secondProduct->id}/options",
            [
                'name' => 'Color',
            ]
        )->assertCreated();
    }

    public function test_duplicate_option_value_is_rejected_for_same_option(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product
        );

        $this->createValue(
            $option,
            [
                'value' => 'Black',
            ]
        );

        $this->postJson(
            "/api/admin/products/{$product->id}/options/{$option->id}/values",
            [
                'value' => 'Black',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'value'
            );
    }

    public function test_option_from_another_product_cannot_be_accessed_through_product(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $foreignOption = $this->createOption(
            $secondProduct
        );

        $this->getJson(
            "/api/admin/products/{$firstProduct->id}/options/{$foreignOption->id}"
        )->assertNotFound();

        $this->patchJson(
            "/api/admin/products/{$firstProduct->id}/options/{$foreignOption->id}",
            [
                'name' => 'Illegal Update',
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/admin/products/{$firstProduct->id}/options/{$foreignOption->id}"
        )->assertNotFound();
    }

    public function test_value_from_another_option_cannot_be_accessed_through_option(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $firstOption = $this->createOption(
            $product,
            [
                'name' => 'Color',
            ]
        );

        $secondOption = $this->createOption(
            $product,
            [
                'name' => 'Size',
            ]
        );

        $foreignValue = $this->createValue(
            $secondOption,
            [
                'value' => 'XL',
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/options/{$firstOption->id}/values/{$foreignValue->id}",
            [
                'value' => 'Black',
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/admin/products/{$product->id}/options/{$firstOption->id}/values/{$foreignValue->id}"
        )->assertNotFound();
    }

    public function test_admin_can_delete_unused_option_value(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product
        );

        $value = $this->createValue(
            $option
        );

        $this->deleteJson(
            "/api/admin/products/{$product->id}/options/{$option->id}/values/{$value->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'product_option_values',
            [
                'id' => $value->id,
            ]
        );
    }

    public function test_admin_can_delete_unused_option_and_its_values(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product
        );

        $value = $this->createValue(
            $option
        );

        $this->deleteJson(
            "/api/admin/products/{$product->id}/options/{$option->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'product_options',
            [
                'id' => $option->id,
            ]
        );

        $this->assertDatabaseMissing(
            'product_option_values',
            [
                'id' => $value->id,
            ]
        );
    }

    public function test_option_value_used_by_variant_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product
        );

        $value = $this->createValue(
            $option
        );

        $variant = $this->createVariant(
            $product
        );

        $variant
            ->optionValues()
            ->attach($value->id);

        $this->deleteJson(
            "/api/admin/products/{$product->id}/options/{$option->id}/values/{$value->id}"
        )->assertStatus(409);

        $this->assertDatabaseHas(
            'product_option_values',
            [
                'id' => $value->id,
            ]
        );
    }

    public function test_option_with_value_used_by_variant_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $option = $this->createOption(
            $product
        );

        $value = $this->createValue(
            $option
        );

        $variant = $this->createVariant(
            $product
        );

        $variant
            ->optionValues()
            ->attach($value->id);

        $this->deleteJson(
            "/api/admin/products/{$product->id}/options/{$option->id}"
        )->assertStatus(409);

        $this->assertDatabaseHas(
            'product_options',
            [
                'id' => $option->id,
            ]
        );

        $this->assertDatabaseHas(
            'product_option_values',
            [
                'id' => $value->id,
            ]
        );
    }

    private function actingAsAdmin(): User
    {
        /** @var User $admin
         */
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
        return Product::query()->create(
            array_merge(
                [
                    'name' => 'Product '.uniqid(),
                    'slug' => 'product-'.uniqid(),
                    'sku' => 'SKU-'.strtoupper(
                        uniqid()
                    ),
                    'base_price' => 100,
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
        return $product
            ->options()
            ->create(
                array_merge(
                    [
                        'name' => 'Option '.uniqid(),
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
        return $option
            ->values()
            ->create(
                array_merge(
                    [
                        'value' => 'Value '.uniqid(),
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
        return $product
            ->variants()
            ->create(
                array_merge(
                    [
                        'name' => 'Variant '.uniqid(),
                        'sku' => 'VAR-'.strtoupper(
                            uniqid()
                        ),
                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                    $attributes
                )
            );
    }
}
