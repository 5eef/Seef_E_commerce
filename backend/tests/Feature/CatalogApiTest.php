<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_only_active_root_categories_with_active_children(): void
    {
        $root = Category::query()
            ->create([
                'name' => 'Electronics',
                'slug' => 'electronics',
                'is_active' => true,
                'sort_order' => 1,
            ]);

        Category::query()->create([
            'parent_id' => $root->id,
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'parent_id' => $root->id,
            'name' => 'Hidden child',
            'slug' => 'hidden-child',
            'is_active' => false,
        ]);

        Category::query()->create([
            'name' => 'Hidden root',
            'slug' => 'hidden-root',
            'is_active' => false,
        ]);

        $response = $this->getJson(
            '/api/categories'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'electronics'
            )
            ->assertJsonCount(
                1,
                'data.0.children'
            )
            ->assertJsonPath(
                'data.0.children.0.slug',
                'phones'
            );

        $this->assertStringNotContainsString(
            'hidden-root',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'hidden-child',
            $response->getContent()
        );
    }

    public function test_inactive_category_cannot_be_opened_publicly(): void
    {
        Category::query()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->getJson(
            '/api/categories/hidden'
        )->assertNotFound();
    }

    public function test_products_endpoint_returns_only_currently_published_active_products(): void
    {
        $published = $this->createProduct([
            'name' => 'Published',
            'slug' => 'published',
        ]);

        $this->createProduct([
            'name' => 'Draft',
            'slug' => 'draft',
            'status' => 'draft',
        ]);

        $this->createProduct([
            'name' => 'Future',
            'slug' => 'future',
            'published_at' => now()
                ->addDay(),
        ]);

        $this->createProduct([
            'name' => 'Unscheduled',
            'slug' => 'unscheduled',
            'published_at' => null,
        ]);

        $deleted = $this->createProduct([
            'name' => 'Deleted',
            'slug' => 'deleted',
        ]);

        $deleted->delete();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $published->id
            )
            ->assertJsonPath(
                'data.0.slug',
                'published'
            );
    }

    public function test_product_can_be_found_by_slug_and_only_public_nested_data_is_returned(): void
    {
        $product = $this->createProduct([
            'name' => 'Laptop Pro',
            'slug' => 'laptop-pro',
            'cost_price' => 500,
        ]);

        $category = Category::query()
            ->create([
                'name' => 'Computers',
                'slug' => 'computers',
                'is_active' => true,
            ]);

        $product
            ->categories()
            ->attach($category);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'disk' => 'public',
            'path' => 'products/laptop.jpg',
            'alt_text' => 'Laptop',
            'is_primary' => true,
        ]);

        $variant = ProductVariant::query()
            ->create([
                'product_id' => $product->id,
                'name' => '16 GB',
                'sku' => 'LAPTOP-16',
                'barcode' => 'PRIVATE-BARCODE',
                'price' => 1200,
                'cost_price' => 600,
                'is_active' => true,
            ]);

        Inventory::query()->create([
            'product_variant_id' => $variant->id,
            'on_hand_quantity' => 10,
            'reserved_quantity' => 2,
            'low_stock_threshold' => 3,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Inactive',
            'sku' => 'LAPTOP-INACTIVE',
            'price' => 1000,
            'is_active' => false,
        ]);

        $option = ProductOption::query()
            ->create([
                'product_id' => $product->id,
                'name' => 'Memory',
            ]);

        $value = ProductOptionValue::query()
            ->create([
                'product_option_id' => $option->id,
                'value' => '16 GB',
            ]);

        $variant
            ->optionValues()
            ->attach($value);

        $approvedUser =
            User::factory()->create();

        $pendingUser =
            User::factory()->create();

        Review::query()->create([
            'user_id' => $approvedUser->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Excellent',
            'status' => 'approved',
            'is_verified_purchase' => true,
            'approved_at' => now(),
        ]);

        Review::query()->create([
            'user_id' => $pendingUser->id,
            'product_id' => $product->id,
            'rating' => 1,
            'title' => 'Pending review',
            'status' => 'pending',
        ]);

        $response = $this->getJson(
            '/api/products/laptop-pro'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.slug',
                'laptop-pro'
            )
            ->assertJsonCount(
                1,
                'data.variants'
            )
            ->assertJsonPath(
                'data.variants.0.sku',
                'LAPTOP-16'
            )
            ->assertJsonPath(
                'data.variants.0.availability.in_stock',
                true
            )
            ->assertJsonCount(
                1,
                'data.options'
            )
            ->assertJsonCount(
                1,
                'data.reviews'
            )
            ->assertJsonPath(
                'data.reviews.0.title',
                'Excellent'
            )
            ->assertJsonPath(
                'data.review_count',
                1
            );

        $this->assertStringNotContainsString(
            'PRIVATE-BARCODE',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'Pending review',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'cost_price',
            $response->getContent()
        );
    }

    public function test_unpublished_product_cannot_be_opened_by_slug(): void
    {
        $this->createProduct([
            'slug' => 'draft-product',
            'status' => 'draft',
        ]);

        $this->getJson(
            '/api/products/draft-product'
        )->assertNotFound();
    }

    public function test_products_can_be_filtered_by_search_and_category(): void
    {
        $computers = Category::query()
            ->create([
                'name' => 'Computers',
                'slug' => 'computers',
                'is_active' => true,
            ]);

        $phones = Category::query()
            ->create([
                'name' => 'Phones',
                'slug' => 'phones',
                'is_active' => true,
            ]);

        $laptop = $this->createProduct([
            'name' => 'Gaming Laptop',
            'slug' => 'gaming-laptop',
        ]);

        $laptop
            ->categories()
            ->attach($computers);

        $phone = $this->createProduct([
            'name' => 'Smart Phone',
            'slug' => 'smart-phone',
        ]);

        $phone
            ->categories()
            ->attach($phones);

        $this->getJson(
            '/api/products?q=laptop&category=computers'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'gaming-laptop'
            );
    }

    public function test_products_use_effective_sale_price_for_price_filters(): void
    {
        $this->createProduct([
            'name' => 'Promotion',
            'slug' => 'promotion',
            'base_price' => 150,
            'sale_price' => 80,
        ]);

        $this->createProduct([
            'name' => 'Expensive',
            'slug' => 'expensive',
            'base_price' => 200,
            'sale_price' => null,
        ]);

        $this->getJson(
            '/api/products?min_price=70&max_price=90'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'promotion'
            );
    }

    public function test_products_can_be_filtered_by_featured_and_stock_availability(): void
    {
        $available =
            $this->createProduct([
                'name' => 'Available featured',
                'slug' => 'available-featured',
                'is_featured' => true,
            ]);

        $this->createInventoryForProduct(
            $available,
            10,
            2
        );

        $out =
            $this->createProduct([
                'name' => 'Out featured',
                'slug' => 'out-featured',
                'is_featured' => true,
            ]);

        $this->createInventoryForProduct(
            $out,
            2,
            2
        );

        $normal =
            $this->createProduct([
                'name' => 'Available normal',
                'slug' => 'available-normal',
                'is_featured' => false,
            ]);

        $this->createInventoryForProduct(
            $normal,
            10,
            0
        );

        $this->getJson(
            '/api/products?featured=true&in_stock=true'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'available-featured'
            );
    }

    public function test_products_support_price_sorting_and_pagination(): void
    {
        $this->createProduct([
            'name' => 'Product C',
            'slug' => 'product-c',
            'base_price' => 300,
        ]);

        $this->createProduct([
            'name' => 'Product A',
            'slug' => 'product-a',
            'base_price' => 100,
        ]);

        $this->createProduct([
            'name' => 'Product B',
            'slug' => 'product-b',
            'base_price' => 200,
        ]);

        $this->getJson(
            '/api/products?sort=price_asc&per_page=2'
        )
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'data.0.slug',
                'product-a'
            )
            ->assertJsonPath(
                'data.1.slug',
                'product-b'
            )
            ->assertJsonPath(
                'meta.per_page',
                2
            )
            ->assertJsonPath(
                'meta.total',
                3
            );
    }

    public function test_invalid_catalog_filters_are_rejected(): void
    {
        $this->getJson(
            '/api/products?min_price=200&max_price=100'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'min_price'
            );

        $this->getJson(
            '/api/products?sort=unsupported'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sort'
            );

        $this->getJson(
            '/api/products?per_page=500'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'per_page'
            );
    }

    private function createProduct(
        array $attributes = []
    ): Product {
        $token = Str::lower(
            Str::random(10)
        );

        return Product::query()
            ->create(
                array_merge(
                    [
                        'name' => 'Product '.$token,

                        'slug' => 'product-'.$token,

                        'sku' => 'SKU-'.Str::upper(
                            $token
                        ),

                        'base_price' => 100,

                        'sale_price' => null,

                        'status' => 'active',

                        'is_featured' => false,

                        'published_at' => now()->subMinute(),
                    ],
                    $attributes
                )
            );
    }

    private function createInventoryForProduct(
        Product $product,
        int $onHand,
        int $reserved
    ): Inventory {
        $variant = ProductVariant::query()
            ->create([
                'product_id' => $product->id,

                'name' => 'Default',

                'sku' => 'VARIANT-'.Str::upper(
                    Str::random(12)
                ),

                'price' => $product->base_price,

                'is_active' => true,
            ]);

        return Inventory::query()
            ->create([
                'product_variant_id' => $variant->id,

                'on_hand_quantity' => $onHand,

                'reserved_quantity' => $reserved,

                'low_stock_threshold' => 5,
            ]);
    }
}
