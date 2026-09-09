<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Store\AddCartItemRequest;
use App\Http\Requests\Store\CheckoutRequest;
use App\Http\Requests\Store\CreateReviewRequest;
use App\Http\Requests\Store\StoreAddressRequest;
use App\Http\Requests\Store\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')
            ->post(
                '/__test/admin/products',
                function (
                    StoreProductRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->patch(
                '/__test/admin/products/{product}',
                function (
                    UpdateProductRequest $request,
                    Product $product
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->post(
                '/__test/admin/categories',
                function (
                    StoreCategoryRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->patch(
                '/__test/admin/categories/{category}',
                function (
                    UpdateCategoryRequest $request,
                    Category $category
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->post(
                '/__test/cart/items',
                function (
                    AddCartItemRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->post(
                '/__test/checkout',
                function (
                    CheckoutRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->post(
                '/__test/reviews',
                function (
                    CreateReviewRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->post(
                '/__test/addresses',
                function (
                    StoreAddressRequest $request
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );

        Route::middleware('web')
            ->patch(
                '/__test/addresses/{address}',
                function (
                    UpdateAddressRequest $request,
                    Address $address
                ) {
                    return response()->json(
                        $request->validated()
                    );
                }
            );
    }

    public function test_active_admin_can_submit_valid_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'web')
            ->postJson(
                '/__test/admin/products',
                [
                    'name' => 'Test Product',
                    'base_price' => 300,
                    'sale_price' => 250,
                    'status' => 'draft',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'slug',
                'test-product'
            );
    }

    public function test_customer_cannot_submit_admin_product_request(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'web')
            ->postJson(
                '/__test/admin/products',
                [
                    'name' => 'Test Product',
                    'base_price' => 300,
                ]
            )
            ->assertForbidden();
    }

    public function test_product_rejects_sale_price_above_base_price(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'web')
            ->postJson(
                '/__test/admin/products',
                [
                    'name' => 'Invalid Price',
                    'base_price' => 100,
                    'sale_price' => 150,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );
    }

    public function test_product_update_ignores_its_own_unique_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $product = $this->createProduct([
            'slug' => 'product-one',
            'sku' => 'PRODUCT-ONE',
        ]);

        $this->actingAs($admin, 'web')
            ->patchJson(
                "/__test/admin/products/{$product->id}",
                [
                    'slug' => 'product-one',
                    'sku' => 'PRODUCT-ONE',
                ]
            )
            ->assertOk();
    }

    public function test_product_update_rejects_another_product_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $productOne = $this->createProduct([
            'slug' => 'product-one',
            'sku' => 'PRODUCT-ONE',
        ]);

        $this->createProduct([
            'slug' => 'product-two',
            'sku' => 'PRODUCT-TWO',
        ]);

        $this->actingAs($admin, 'web')
            ->patchJson(
                "/__test/admin/products/{$productOne->id}",
                [
                    'slug' => 'product-two',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'slug'
            );
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $category = Category::query()->create([
            'name' => 'Phones',
            'slug' => 'phones',
        ]);

        $this->actingAs($admin, 'web')
            ->patchJson(
                "/__test/admin/categories/{$category->id}",
                [
                    'parent_id' => $category->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'parent_id'
            );
    }

    public function test_guest_checkout_accepts_safe_payload_and_normalizes_data(): void
    {
        $this->postJson(
            '/__test/checkout',
            [
                'email' => ' TEST@EXAMPLE.COM ',
                'phone' => '0612345678',

                'shipping_address' => [
                    'first_name' => 'Youssef',
                    'last_name' => 'Boughioul',
                    'phone' => '0612345678',
                    'address_line_1' => 'Test address',
                    'city' => 'Sefrou',
                    'country_code' => 'ma',
                ],

                'billing_same_as_shipping' => true,

                'payment_method' => 'cod',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'email',
                'test@example.com'
            )
            ->assertJsonPath(
                'shipping_address.country_code',
                'MA'
            );
    }

    public function test_checkout_rejects_client_controlled_total(): void
    {
        $this->postJson(
            '/__test/checkout',
            [
                'email' => 'customer@example.com',
                'phone' => '0612345678',

                'shipping_address' => [
                    'first_name' => 'Customer',
                    'last_name' => 'Test',
                    'phone' => '0612345678',
                    'address_line_1' => 'Address',
                    'city' => 'Sefrou',
                    'country_code' => 'MA',
                ],

                'payment_method' => 'cod',

                'grand_total' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'grand_total'
            );
    }

    public function test_review_rejects_invalid_rating_and_moderation_fields(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'web')
            ->postJson(
                '/__test/reviews',
                [
                    'rating' => 6,
                    'status' => 'approved',
                    'is_verified_purchase' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rating',
                'status',
                'is_verified_purchase',
            ]);
    }

    public function test_address_country_code_is_normalized(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'web')
            ->postJson(
                '/__test/addresses',
                [
                    'first_name' => 'Youssef',
                    'last_name' => 'Boughioul',
                    'phone' => '0612345678',
                    'address_line_1' => 'Address',
                    'city' => 'Sefrou',
                    'country_code' => 'ma',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'country_code',
                'MA'
            );
    }

    public function test_address_owner_can_update_but_another_customer_cannot(): void
    {
        $owner = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $other = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $address = $owner
            ->addresses()
            ->create([
                'first_name' => 'Owner',
                'last_name' => 'Customer',
                'phone' => '0612345678',
                'address_line_1' => 'Address',
                'city' => 'Sefrou',
                'country_code' => 'MA',
            ]);

        $this->actingAs($owner, 'web')
            ->patchJson(
                "/__test/addresses/{$address->id}",
                [
                    'city' => 'Fes',
                ]
            )
            ->assertOk();

        $this->actingAs($other, 'web')
            ->patchJson(
                "/__test/addresses/{$address->id}",
                [
                    'city' => 'Rabat',
                ]
            )
            ->assertForbidden();
    }

    public function test_cart_accepts_only_active_non_deleted_variant(): void
    {
        $product = $this->createProduct();

        $activeVariant = ProductVariant::query()
            ->create([
                'product_id' => $product->id,
                'name' => 'Default',
                'sku' => 'ACTIVE-VARIANT',
                'price' => 300,
                'is_active' => true,
            ]);

        $inactiveVariant = ProductVariant::query()
            ->create([
                'product_id' => $product->id,
                'name' => 'Inactive',
                'sku' => 'INACTIVE-VARIANT',
                'price' => 300,
                'is_active' => false,
            ]);

        $this->postJson(
            '/__test/cart/items',
            [
                'product_variant_id' => $activeVariant->id,

                'quantity' => 2,
            ]
        )->assertOk();

        $this->postJson(
            '/__test/cart/items',
            [
                'product_variant_id' => $inactiveVariant->id,

                'quantity' => 2,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );
    }

    private function createProduct(
        array $attributes = []
    ): Product {
        return Product::query()->create(
            array_merge(
                [
                    'name' => 'Product',
                    'slug' => 'product-'.uniqid(),
                    'sku' => 'SKU-'.uniqid(),
                    'base_price' => 300,
                    'status' => 'draft',
                ],
                $attributes
            )
        );
    }
}
