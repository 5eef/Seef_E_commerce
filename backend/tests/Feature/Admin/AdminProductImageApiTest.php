<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImageApiTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guest_cannot_access_product_images(): void
    {
        $product = $this->createProduct();

        $this->getJson(
            "/api/admin/products/{$product->id}/images"
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_product_images(): void
    {
        /**
         * @var mixed
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
            "/api/admin/products/{$product->id}/images"
        )->assertForbidden();
    }

    public function test_admin_can_upload_first_image_and_it_becomes_primary(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $file = $this->fakeImage(
            'main-product.png',
            500
        );

        $response = $this->post(
            "/api/admin/products/{$product->id}/images",
            [
                'image' => $file,
                'alt_text' => '  Main product image  ',
                'sort_order' => 5,
                'is_primary' => false,
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.product_id',
                $product->id
            )
            ->assertJsonPath(
                'data.disk',
                'public'
            )
            ->assertJsonPath(
                'data.alt_text',
                'Main product image'
            )
            ->assertJsonPath(
                'data.sort_order',
                5
            )
            ->assertJsonPath(
                'data.is_primary',
                true
            );

        $path = $response->json(
            'data.path'
        );

        $this->assertIsString(
            $path
        );

        $this->assertTrue(
            Storage::disk('public')->exists($path)
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'product_id' => $product->id,
                'disk' => 'public',
                'path' => $path,
                'alt_text' => 'Main product image',
                'sort_order' => 5,
                'is_primary' => true,
            ]
        );
    }

    public function test_only_one_image_can_be_primary(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $first = $this->uploadImage(
            $product,
            'first.png'
        );

        $second = $this->uploadImage(
            $product,
            'second.png',
            [
                'is_primary' => false,
            ]
        );

        $first
            ->assertCreated()
            ->assertJsonPath(
                'data.is_primary',
                true
            );

        $second
            ->assertCreated()
            ->assertJsonPath(
                'data.is_primary',
                false
            );

        $third = $this->uploadImage(
            $product,
            'third.png',
            [
                'is_primary' => true,
            ]
        );

        $third
            ->assertCreated()
            ->assertJsonPath(
                'data.is_primary',
                true
            );

        $firstId = $first->json(
            'data.id'
        );

        $thirdId = $third->json(
            'data.id'
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $firstId,
                'is_primary' => false,
            ]
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $thirdId,
                'is_primary' => true,
            ]
        );

        $this->assertSame(
            1,
            ProductImage::query()
                ->where(
                    'product_id',
                    $product->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_admin_can_list_images_primary_first_then_by_sort_order(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $late = $this->createStoredImage(
            $product,
            [
                'sort_order' => 20,
                'is_primary' => false,
            ]
        );

        $primary = $this->createStoredImage(
            $product,
            [
                'sort_order' => 50,
                'is_primary' => true,
            ]
        );

        $early = $this->createStoredImage(
            $product,
            [
                'sort_order' => 10,
                'is_primary' => false,
            ]
        );

        $response = $this->getJson(
            "/api/admin/products/{$product->id}/images"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                3,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $primary->id
            )
            ->assertJsonPath(
                'data.1.id',
                $early->id
            )
            ->assertJsonPath(
                'data.2.id',
                $late->id
            );
    }

    public function test_admin_can_show_product_image(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $image = $this->createStoredImage(
            $product,
            [
                'alt_text' => 'Front view',
                'is_primary' => true,
            ]
        );

        $this->getJson(
            "/api/admin/products/{$product->id}/images/{$image->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $image->id
            )
            ->assertJsonPath(
                'data.product_id',
                $product->id
            )
            ->assertJsonPath(
                'data.disk',
                'public'
            )
            ->assertJsonPath(
                'data.path',
                $image->path
            )
            ->assertJsonPath(
                'data.alt_text',
                'Front view'
            )
            ->assertJsonPath(
                'data.is_primary',
                true
            );
    }

    public function test_admin_can_manage_seeded_external_image(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();
        $url = 'https://images.example.test/demo-product.jpg';
        $image = $product->images()->create([
            'disk' => 'external',
            'path' => $url,
            'alt_text' => 'External demo image',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $this->getJson(
            "/api/admin/products/{$product->id}/images/{$image->id}"
        )
            ->assertOk()
            ->assertJsonPath('data.url', $url);

        $this->deleteJson(
            "/api/admin/products/{$product->id}/images/{$image->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing('product_images', [
            'id' => $image->id,
        ]);
    }

    public function test_admin_can_update_image_metadata_and_make_it_primary(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $first = $this->createStoredImage(
            $product,
            [
                'is_primary' => true,
                'sort_order' => 1,
            ]
        );

        $second = $this->createStoredImage(
            $product,
            [
                'is_primary' => false,
                'sort_order' => 2,
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/images/{$second->id}",
            [
                'alt_text' => '  New primary image  ',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.alt_text',
                'New primary image'
            )
            ->assertJsonPath(
                'data.sort_order',
                0
            )
            ->assertJsonPath(
                'data.is_primary',
                true
            );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $first->id,
                'is_primary' => false,
            ]
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $second->id,
                'alt_text' => 'New primary image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $this->assertSame(
            1,
            ProductImage::query()
                ->where(
                    'product_id',
                    $product->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_primary_image_cannot_be_unset_when_it_is_the_only_image(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $image = $this->createStoredImage(
            $product,
            [
                'is_primary' => true,
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/images/{$image->id}",
            [
                'is_primary' => false,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_primary',
                true
            );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $image->id,
                'is_primary' => true,
            ]
        );
    }

    public function test_unsetting_primary_promotes_next_image(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $primary = $this->createStoredImage(
            $product,
            [
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $replacement = $this->createStoredImage(
            $product,
            [
                'sort_order' => 1,
                'is_primary' => false,
            ]
        );

        $this->patchJson(
            "/api/admin/products/{$product->id}/images/{$primary->id}",
            [
                'is_primary' => false,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_primary',
                false
            );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $replacement->id,
                'is_primary' => true,
            ]
        );

        $this->assertSame(
            1,
            ProductImage::query()
                ->where(
                    'product_id',
                    $product->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_admin_can_replace_image_file_and_old_file_is_deleted(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $image = $this->createStoredImage(
            $product,
            [
                'is_primary' => true,
            ]
        );

        $oldPath = $image->path;

        $this->assertTrue(
            Storage::disk('public')->exists($oldPath)
        );

        $replacement = $this->fakeImage(
            'replacement.png',
            400
        );

        $response = $this->call(
            'PATCH',
            "/api/admin/products/{$product->id}/images/{$image->id}",
            [
                'alt_text' => 'Replacement image',
            ],
            [],
            [
                'image' => $replacement,
            ],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.alt_text',
                'Replacement image'
            )
            ->assertJsonPath(
                'data.disk',
                'public'
            );

        $newPath = $response->json(
            'data.path'
        );

        $this->assertIsString(
            $newPath
        );

        $this->assertNotSame(
            $oldPath,
            $newPath
        );

        $this->assertFalse(
            Storage::disk('public')->exists($oldPath)
        );

        $this->assertTrue(
            Storage::disk('public')->exists($newPath)
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $image->id,
                'path' => $newPath,
                'disk' => 'public',
            ]
        );
    }

    public function test_non_image_file_is_rejected(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $file = UploadedFile::fake()->create(
            'payload.php',
            20,
            'application/x-php'
        );

        $this->post(
            "/api/admin/products/{$product->id}/images",
            [
                'image' => $file,
            ],
            [
                'Accept' => 'application/json',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'image'
            );

        $this->assertSame(
            0,
            $product
                ->images()
                ->count()
        );
    }

    public function test_image_larger_than_five_megabytes_is_rejected(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $file = $this->fakeImage(
            'too-large.png',
            6000
        );

        $this->post(
            "/api/admin/products/{$product->id}/images",
            [
                'image' => $file,
            ],
            [
                'Accept' => 'application/json',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'image'
            );
    }

    public function test_client_cannot_control_disk_path_or_product_id(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $file = $this->fakeImage(
            'safe.png',
            100
        );

        $this->post(
            "/api/admin/products/{$product->id}/images",
            [
                'image' => $file,

                'product_id' => 999999,

                'disk' => 'local',

                'path' => '../../private/secret.txt',
            ],
            [
                'Accept' => 'application/json',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'product_id',
                'disk',
                'path',
            ]);

        $this->assertSame(
            0,
            $product
                ->images()
                ->count()
        );
    }

    public function test_image_from_another_product_cannot_be_accessed_through_product(): void
    {
        $this->actingAsAdmin();

        $firstProduct = $this->createProduct();
        $secondProduct = $this->createProduct();

        $foreignImage = $this->createStoredImage(
            $secondProduct,
            [
                'is_primary' => true,
            ]
        );

        $this->getJson(
            "/api/admin/products/{$firstProduct->id}/images/{$foreignImage->id}"
        )->assertNotFound();

        $this->patchJson(
            "/api/admin/products/{$firstProduct->id}/images/{$foreignImage->id}",
            [
                'alt_text' => 'Illegal update',
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/admin/products/{$firstProduct->id}/images/{$foreignImage->id}"
        )->assertNotFound();

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $foreignImage->id,
                'product_id' => $secondProduct->id,
            ]
        );

        $this->assertTrue(
            Storage::disk('public')->exists($foreignImage->path)
        );
    }

    public function test_admin_can_delete_non_primary_image_and_file(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $primary = $this->createStoredImage(
            $product,
            [
                'is_primary' => true,
                'sort_order' => 0,
            ]
        );

        $secondary = $this->createStoredImage(
            $product,
            [
                'is_primary' => false,
                'sort_order' => 1,
            ]
        );

        $this->deleteJson(
            "/api/admin/products/{$product->id}/images/{$secondary->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'product_images',
            [
                'id' => $secondary->id,
            ]
        );

        $this->assertFalse(
            Storage::disk('public')->exists($secondary->path)
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $primary->id,
                'is_primary' => true,
            ]
        );

        $this->assertTrue(
            Storage::disk('public')->exists($primary->path)
        );
    }

    public function test_deleting_primary_image_promotes_next_image(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct();

        $primary = $this->createStoredImage(
            $product,
            [
                'is_primary' => true,
                'sort_order' => 0,
            ]
        );

        $next = $this->createStoredImage(
            $product,
            [
                'is_primary' => false,
                'sort_order' => 10,
            ]
        );

        $later = $this->createStoredImage(
            $product,
            [
                'is_primary' => false,
                'sort_order' => 20,
            ]
        );

        $this->deleteJson(
            "/api/admin/products/{$product->id}/images/{$primary->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing(
            'product_images',
            [
                'id' => $primary->id,
            ]
        );

        $this->assertFalse(
            Storage::disk('public')->exists($primary->path)
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $next->id,
                'is_primary' => true,
            ]
        );

        $this->assertDatabaseHas(
            'product_images',
            [
                'id' => $later->id,
                'is_primary' => false,
            ]
        );

        $this->assertSame(
            1,
            ProductImage::query()
                ->where(
                    'product_id',
                    $product->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    private function fakeImage(
        string $filename = 'image.png',
        int $kilobytes = 100
    ): UploadedFile {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );

        if ($content === false) {
            throw new \RuntimeException(
                'Unable to create the fake PNG fixture.'
            );
        }

        $targetBytes = $kilobytes * 1024;

        if (strlen($content) < $targetBytes) {
            $content .= str_repeat(
                "\0",
                $targetBytes - strlen($content)
            );
        }

        return UploadedFile::fake()
            ->createWithContent(
                $filename,
                $content
            );
    }

    private function actingAsAdmin(): User
    {
        /**
         * @var mixed
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

    private function createStoredImage(
        Product $product,
        array $attributes = []
    ): ProductImage {
        $sequence = ++$this->sequence;

        $path = "products/{$product->id}/fixture-{$sequence}.jpg";

        Storage::disk('public')
            ->put(
                $path,
                "image-fixture-{$sequence}"
            );

        return $product
            ->images()
            ->create(
                array_merge(
                    [
                        'disk' => 'public',
                        'path' => $path,
                        'alt_text' => "Image {$sequence}",
                        'sort_order' => 0,
                        'is_primary' => false,
                    ],
                    $attributes
                )
            );
    }

    private function uploadImage(
        Product $product,
        string $filename,
        array $attributes = []
    ) {
        return $this->post(
            "/api/admin/products/{$product->id}/images",
            array_merge(
                [
                    'image' => $this->fakeImage(
                        $filename,
                        100
                    ),
                ],
                $attributes
            ),
            [
                'Accept' => 'application/json',
            ]
        );
    }
}
