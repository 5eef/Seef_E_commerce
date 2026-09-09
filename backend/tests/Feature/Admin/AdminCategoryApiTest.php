<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_categories(): void
    {
        $this->getJson(
            '/api/admin/categories'
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_categories(): void
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
            '/api/admin/categories'
        )->assertForbidden();
    }

    public function test_suspended_admin_cannot_access_admin_categories(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $this->actingAs(
            $admin,
            'web'
        );

        $this->getJson(
            '/api/admin/categories'
        )->assertForbidden();
    }

    public function test_active_admin_can_create_category(): void
    {
        $this->actingAsAdmin();

        $this->postJson(
            '/api/admin/categories',
            [
                'name' => 'Mobile Phones',
                'slug' => 'mobile-phones',
                'description' => 'Phones',
                'is_active' => true,
                'sort_order' => 10,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Mobile Phones'
            )
            ->assertJsonPath(
                'data.slug',
                'mobile-phones'
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $this->assertDatabaseHas(
            'categories',
            [
                'name' => 'Mobile Phones',
                'slug' => 'mobile-phones',
            ]
        );
    }

    public function test_admin_category_listing_supports_search_and_active_filter(): void
    {
        $this->actingAsAdmin();

        Category::query()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        Category::query()->create([
            'name' => 'Hidden Phones',
            'slug' => 'hidden-phones',
            'is_active' => false,
        ]);

        Category::query()->create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'is_active' => true,
        ]);

        $this->getJson(
            '/api/admin/categories?q=phones&is_active=true'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'phones'
            );
    }

    public function test_active_admin_can_update_category(): void
    {
        $this->actingAsAdmin();

        $category = Category::query()
            ->create([
                'name' => 'Phones',
                'slug' => 'phones',
                'is_active' => true,
            ]);

        $this->patchJson(
            "/api/admin/categories/{$category->id}",
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'sort_order' => 5,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Smartphones'
            )
            ->assertJsonPath(
                'data.slug',
                'smartphones'
            );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $category->id,
                'name' => 'Smartphones',
                'slug' => 'smartphones',
            ]
        );
    }

    public function test_category_hierarchy_cycle_is_rejected(): void
    {
        $this->actingAsAdmin();

        $root = Category::query()
            ->create([
                'name' => 'Root',
                'slug' => 'root',
            ]);

        $child = Category::query()
            ->create([
                'parent_id' => $root->id,
                'name' => 'Child',
                'slug' => 'child',
            ]);

        $grandchild = Category::query()
            ->create([
                'parent_id' => $child->id,
                'name' => 'Grandchild',
                'slug' => 'grandchild',
            ]);

        $this->patchJson(
            "/api/admin/categories/{$root->id}",
            [
                'parent_id' => $grandchild->id,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'parent_id'
            );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $root->id,
                'parent_id' => null,
            ]
        );
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $parent = Category::query()
            ->create([
                'name' => 'Parent',
                'slug' => 'parent',
            ]);

        Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Child',
            'slug' => 'child',
        ]);

        $this->deleteJson(
            "/api/admin/categories/{$parent->id}"
        )->assertConflict();

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $parent->id,
            ]
        );
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $category = Category::query()
            ->create([
                'name' => 'Phones',
                'slug' => 'phones',
            ]);

        $product = Product::query()
            ->create([
                'name' => 'Phone',
                'slug' => 'phone',
                'sku' => 'PHONE-001',
                'base_price' => 1000,
                'status' => 'draft',
            ]);

        $category
            ->products()
            ->attach($product);

        $this->deleteJson(
            "/api/admin/categories/{$category->id}"
        )->assertConflict();

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $category->id,
            ]
        );
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $this->actingAsAdmin();

        $category = Category::query()
            ->create([
                'name' => 'Temporary',
                'slug' => 'temporary',
            ]);

        $this->deleteJson(
            "/api/admin/categories/{$category->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'categories',
            [
                'id' => $category->id,
            ]
        );
    }

    private function actingAsAdmin(): User
    {
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
}
