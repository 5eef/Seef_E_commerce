<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_products(): void
    {
        $this->getJson(
            '/api/admin/products'
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_products(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $this->getJson(
            '/api/admin/products'
        )->assertForbidden();
    }

    public function test_active_admin_can_create_product_with_categories(): void
    {
        $this->actingAsAdmin();

        $phones = Category::query()->create([
            'name' => 'Phones',
            'slug' => 'phones',
        ]);

        $featured = Category::query()->create([
            'name' => 'Featured',
            'slug' => 'featured',
        ]);

        $response = $this->postJson(
            '/api/admin/products',
            [
                'name' => 'Phone Pro',
                'slug' => 'phone-pro',
                'sku' => 'phone-pro-001',

                'short_description' => 'Premium phone',

                'base_price' => 5000,
                'sale_price' => 4500,
                'cost_price' => 3000,

                'status' => 'draft',
                'is_featured' => true,

                'category_ids' => [
                    $phones->id,
                    $featured->id,
                ],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Phone Pro'
            )
            ->assertJsonPath(
                'data.slug',
                'phone-pro'
            )
            ->assertJsonPath(
                'data.sku',
                'PHONE-PRO-001'
            )
            ->assertJsonPath(
                'data.base_price',
                '5000.00'
            )
            ->assertJsonPath(
                'data.sale_price',
                '4500.00'
            )
            ->assertJsonPath(
                'data.cost_price',
                '3000.00'
            )
            ->assertJsonPath(
                'data.is_featured',
                true
            )
            ->assertJsonCount(
                2,
                'data.categories'
            );

        $product = Product::query()
            ->where(
                'slug',
                'phone-pro'
            )
            ->firstOrFail();

        $this->assertDatabaseHas(
            'category_product',
            [
                'category_id' => $phones->id,
                'product_id' => $product->id,
            ]
        );

        $this->assertDatabaseHas(
            'category_product',
            [
                'category_id' => $featured->id,
                'product_id' => $product->id,
            ]
        );
    }

    public function test_admin_listing_supports_search_status_category_and_featured_filters(): void
    {
        $this->actingAsAdmin();

        $phones = Category::query()->create([
            'name' => 'Phones',
            'slug' => 'phones',
        ]);

        $target = $this->createProduct([
            'name' => 'Gaming Phone',
            'slug' => 'gaming-phone',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $target
            ->categories()
            ->attach($phones);

        $this->createProduct([
            'name' => 'Gaming Laptop',
            'slug' => 'gaming-laptop',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $other = $this->createProduct([
            'name' => 'Draft Gaming Phone',
            'slug' => 'draft-gaming-phone',
            'status' => 'draft',
            'is_featured' => false,
        ]);

        $other
            ->categories()
            ->attach($phones);

        $this->getJson(
            "/api/admin/products?q=gaming&status=active&category_id={$phones->id}&featured=true"
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $target->id
            );
    }

    public function test_admin_listing_supports_sorting_and_pagination(): void
    {
        $this->actingAsAdmin();

        $this->createProduct([
            'name' => 'Expensive',
            'base_price' => 900,
        ]);

        $cheap = $this->createProduct([
            'name' => 'Cheap',
            'base_price' => 100,
        ]);

        $this->createProduct([
            'name' => 'Medium',
            'base_price' => 500,
        ]);

        $this->getJson(
            '/api/admin/products?sort=price_asc&per_page=2'
        )
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

    public function test_invalid_admin_product_filters_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->getJson(
            '/api/admin/products?status=invalid'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $this->getJson(
            '/api/admin/products?trashed=invalid'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'trashed'
            );

        $this->getJson(
            '/api/admin/products?sort=invalid'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sort'
            );

        $this->getJson(
            '/api/admin/products?per_page=500'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'per_page'
            );
    }

    public function test_active_admin_can_view_product_internal_data(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'cost_price' => 55,
        ]);

        $response = $this->getJson(
            "/api/admin/products/{$product->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $product->id
            )
            ->assertJsonPath(
                'data.cost_price',
                '55.00'
            )
            ->assertJsonPath(
                'data.variants_count',
                0
            )
            ->assertJsonPath(
                'data.images_count',
                0
            );

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'guest_token',
            $content
        );

        $this->assertStringNotContainsString(
            'password',
            $content
        );
    }

    public function test_active_admin_can_update_product_and_sync_categories(): void
    {
        $this->actingAsAdmin();

        $oldCategory = Category::query()->create([
            'name' => 'Old Category',
            'slug' => 'old-category',
        ]);

        $newCategory = Category::query()->create([
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $product = $this->createProduct([
            'name' => 'Original Product',
            'base_price' => 700,
        ]);

        $product
            ->categories()
            ->attach($oldCategory);

        $this->patchJson(
            "/api/admin/products/{$product->id}",
            [
                'name' => 'Updated Product',
                'base_price' => 650,
                'sale_price' => 600,

                'category_ids' => [
                    $newCategory->id,
                ],
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Updated Product'
            )
            ->assertJsonPath(
                'data.base_price',
                '650.00'
            )
            ->assertJsonPath(
                'data.sale_price',
                '600.00'
            )
            ->assertJsonCount(
                1,
                'data.categories'
            )
            ->assertJsonPath(
                'data.categories.0.id',
                $newCategory->id
            );

        $this->assertDatabaseMissing(
            'category_product',
            [
                'category_id' => $oldCategory->id,
                'product_id' => $product->id,
            ]
        );

        $this->assertDatabaseHas(
            'category_product',
            [
                'category_id' => $newCategory->id,
                'product_id' => $product->id,
            ]
        );
    }

    public function test_product_update_rejects_sale_price_above_effective_base_price(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct([
            'base_price' => 500,
        ]);

        $this->patchJson(
            "/api/admin/products/{$product->id}",
            [
                'sale_price' => 700,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );

        $this->patchJson(
            "/api/admin/products/{$product->id}",
            [
                'base_price' => 300,
                'sale_price' => 400,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sale_price'
            );
    }

    public function test_admin_can_soft_delete_and_list_only_deleted_products(): void
    {
        $this->actingAsAdmin();

        $active = $this->createProduct([
            'name' => 'Active Product',
        ]);

        $deleted = $this->createProduct([
            'name' => 'Deleted Product',
        ]);

        $this->deleteJson(
            "/api/admin/products/{$deleted->id}"
        )->assertNoContent();

        $this->assertSoftDeleted(
            'products',
            [
                'id' => $deleted->id,
            ]
        );

        $this->getJson(
            "/api/admin/products/{$deleted->id}"
        )->assertNotFound();

        $response = $this->getJson(
            '/api/admin/products?trashed=only'
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

    public function test_admin_can_restore_soft_deleted_product(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $product->delete();

        $this->postJson(
            "/api/admin/products/{$product->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $product->id
            )
            ->assertJsonPath(
                'data.deleted_at',
                null
            );

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
                'deleted_at' => null,
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
}
