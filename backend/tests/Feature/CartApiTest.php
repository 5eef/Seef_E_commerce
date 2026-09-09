<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Store\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_can_open_cart_and_receives_secure_cookie(): void
    {
        $response = $this->getJson(
            '/api/cart'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'active'
            )
            ->assertJsonPath(
                'data.items_count',
                0
            )
            ->assertJsonPath(
                'data.total_quantity',
                0
            )
            ->assertJsonPath(
                'data.subtotal',
                '0.00'
            )
            ->assertJsonMissingPath(
                'data.guest_token'
            );

        $this->assertDatabaseCount(
            'carts',
            1
        );

        $cart = Cart::query()
            ->firstOrFail();

        $this->assertNull(
            $cart->user_id
        );

        $this->assertNotNull(
            $cart->guest_token
        );

        $this->assertSame(
            'active',
            $cart->status
        );

        $this->assertNotNull(
            $cart->expires_at
        );

        $cookies = collect(
            $response->headers->getCookies()
        );

        $cookie = $cookies->first(
            fn ($cookie) => $cookie->getName()
                === CartService::GUEST_COOKIE
        );

        $this->assertNotNull(
            $cookie
        );

        $this->assertTrue(
            $cookie->isHttpOnly()
        );

        $this->assertSame(
            'lax',
            $cookie->getSameSite()
        );
    }

    public function test_guest_cookie_reuses_same_cart(): void
    {
        $this->getJson(
            '/api/cart'
        )->assertOk();

        $cart = Cart::query()
            ->firstOrFail();

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $cart
                )
            )
            ->getJson(
                '/api/cart'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $cart->id
            );

        $this->assertDatabaseCount(
            'carts',
            1
        );
    }

    public function test_expired_guest_cart_is_not_reused(): void
    {
        $guestCart = $this->createGuestCart([
            'expires_at' => now()
                ->subMinute(),
        ]);

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->getJson(
                '/api/cart'
            );

        $response->assertOk();

        $guestCart->refresh();

        $this->assertSame(
            'expired',
            $guestCart->status
        );

        $this->assertDatabaseCount(
            'carts',
            2
        );

        $this->assertNotSame(
            $guestCart->id,
            $response->json(
                'data.id'
            )
        );
    }

    public function test_authenticated_customer_gets_user_cart(): void
    {
        $customer = $this->actingAsCustomer();

        $response = $this->getJson(
            '/api/cart'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'active'
            );

        $cart = Cart::query()
            ->where(
                'user_id',
                $customer->id
            )
            ->firstOrFail();

        $this->assertSame(
            $cart->id,
            $response->json(
                'data.id'
            )
        );

        $this->assertNull(
            $cart->guest_token
        );

        $this->assertNull(
            $cart->expires_at
        );
    }

    public function test_authenticated_customer_reuses_same_active_cart(): void
    {
        $customer = $this->actingAsCustomer();

        $first = $this->getJson(
            '/api/cart'
        )->assertOk();

        $second = $this->getJson(
            '/api/cart'
        )->assertOk();

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id')
        );

        $this->assertSame(
            1,
            Cart::query()
                ->where(
                    'user_id',
                    $customer->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->count()
        );
    }

    public function test_suspended_customer_cannot_use_cart(): void
    {
        $customer = $this->createCustomer([
            'status' => 'suspended',
        ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $this->getJson(
            '/api/cart'
        )->assertForbidden();

        $variant = $this
            ->createPurchasableVariant();

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]
        )->assertForbidden();
    }

    public function test_customer_can_add_purchasable_variant(): void
    {
        $customer = $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 10,
                    'reserved_quantity' => 2,
                ]
            );

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.total_quantity',
                3
            )
            ->assertJsonPath(
                'data.items.0.quantity',
                3
            )
            ->assertJsonPath(
                'data.items.0.available_quantity',
                8
            )
            ->assertJsonPath(
                'data.items.0.is_available',
                true
            );

        $cart = Cart::query()
            ->where(
                'user_id',
                $customer->id
            )
            ->firstOrFail();

        $this->assertDatabaseHas(
            'cart_items',
            [
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]
        );
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $variant = $this
            ->createPurchasableVariant();

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.total_quantity',
                2
            )
            ->assertJsonMissingPath(
                'data.guest_token'
            );

        $cart = Cart::query()
            ->firstOrFail();

        $this->assertNull(
            $cart->user_id
        );

        $this->assertNotNull(
            $cart->guest_token
        );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]
        );
    }

    public function test_repeated_addition_increases_existing_line_quantity(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 20,
                    'reserved_quantity' => 0,
                ]
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]
        )->assertCreated();

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.items.0.quantity',
                5
            );

        $this->assertSame(
            1,
            CartItem::query()
                ->where(
                    'product_variant_id',
                    $variant->id
                )
                ->count()
        );
    }

    public function test_cart_uses_available_stock_on_hand_minus_reserved(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 10,
                    'reserved_quantity' => 3,
                ]
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 7,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.items.0.available_quantity',
                7
            );

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'quantity'
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 7,
            ]
        );
    }

    public function test_cart_does_not_reserve_inventory_when_item_is_added(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 10,
                    'reserved_quantity' => 4,
                ]
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]
        )->assertCreated();

        $variant
            ->inventory
            ->refresh();

        $this->assertSame(
            10,
            $variant->inventory->on_hand_quantity
        );

        $this->assertSame(
            4,
            $variant->inventory->reserved_quantity
        );
    }

    public function test_variant_without_inventory_cannot_be_added(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                createInventory: false
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_inactive_or_deleted_variant_cannot_be_added(): void
    {
        $this->actingAsCustomer();

        $inactive = $this
            ->createPurchasableVariant(
                variantAttributes: [
                    'is_active' => false,
                ]
            );

        $deleted = $this
            ->createPurchasableVariant();

        $deleted->delete();

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $inactive->id,
                'quantity' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $deleted->id,
                'quantity' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_unpublished_or_deleted_product_cannot_be_added(): void
    {
        $this->actingAsCustomer();

        $unpublishedVariant = $this
            ->createPurchasableVariant(
                productAttributes: [
                    'published_at' => null,
                ]
            );

        $deletedProductVariant = $this
            ->createPurchasableVariant();

        $deletedProductVariant
            ->product
            ->delete();

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $unpublishedVariant->id,
                'quantity' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $deletedProductVariant->id,
                'quantity' => 1,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'product_variant_id'
            );

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_client_cannot_control_cart_price_or_internal_fields(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 1,

                'cart_id' => 999,
                'guest_token' => (string) Str::uuid(),
                'user_id' => 999,

                'price' => 1,
                'sale_price' => 1,
                'unit_price' => 1,
                'subtotal' => 1,
                'total' => 1,

                'on_hand_quantity' => 999999,
                'reserved_quantity' => 0,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cart_id',
                'guest_token',
                'user_id',
                'price',
                'sale_price',
                'unit_price',
                'subtotal',
                'total',
                'on_hand_quantity',
                'reserved_quantity',
            ]);

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_cart_uses_variant_sale_price_and_calculates_totals_server_side(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                productAttributes: [
                    'base_price' => 200,
                    'sale_price' => 150,
                ],
                variantAttributes: [
                    'price' => 120,
                    'sale_price' => 90,
                ]
            );

        $response = $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.items.0.unit_price',
                '90.00'
            )
            ->assertJsonPath(
                'data.items.0.line_total',
                '180.00'
            )
            ->assertJsonPath(
                'data.subtotal',
                '180.00'
            )
            ->assertJsonPath(
                'data.currency',
                'MAD'
            );
    }

    public function test_cart_falls_back_to_product_sale_price_when_variant_has_no_price(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                productAttributes: [
                    'base_price' => 200,
                    'sale_price' => 150,
                ],
                variantAttributes: [
                    'price' => null,
                    'sale_price' => null,
                ]
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.items.0.unit_price',
                '150.00'
            )
            ->assertJsonPath(
                'data.items.0.line_total',
                '300.00'
            )
            ->assertJsonPath(
                'data.subtotal',
                '300.00'
            );
    }

    public function test_cart_quantity_cannot_exceed_ninety_nine(): void
    {
        $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 200,
                    'reserved_quantity' => 0,
                ]
            );

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 60,
            ]
        )->assertCreated();

        $this->postJson(
            '/api/cart/items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 40,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'quantity'
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'product_variant_id' => $variant->id,
                'quantity' => 60,
            ]
        );
    }

    public function test_customer_can_update_cart_item_quantity(): void
    {
        $customer = $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $cart = $this->createUserCart(
            $customer
        );

        $item = $cart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $response = $this->patchJson(
            "/api/cart/items/{$item->id}",
            [
                'quantity' => 5,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.items.0.quantity',
                5
            )
            ->assertJsonPath(
                'data.total_quantity',
                5
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'id' => $item->id,
                'cart_id' => $cart->id,
                'quantity' => 5,
            ]
        );
    }

    public function test_cart_item_update_revalidates_available_stock(): void
    {
        $customer = $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 8,
                    'reserved_quantity' => 3,
                ]
            );

        $cart = $this->createUserCart(
            $customer
        );

        $item = $cart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $this->patchJson(
            "/api/cart/items/{$item->id}",
            [
                'quantity' => 6,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'quantity'
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'id' => $item->id,
                'quantity' => 2,
            ]
        );
    }

    public function test_customer_cannot_update_item_from_another_cart(): void
    {
        $owner = $this->createCustomer();
        $attacker = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $ownerCart = $this->createUserCart(
            $owner
        );

        $item = $ownerCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $this->actingAs(
            $attacker,
            'web'
        );

        $this->patchJson(
            "/api/cart/items/{$item->id}",
            [
                'quantity' => 9,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas(
            'cart_items',
            [
                'id' => $item->id,
                'cart_id' => $ownerCart->id,
                'quantity' => 2,
            ]
        );
    }

    public function test_customer_can_remove_own_cart_item(): void
    {
        $customer = $this->actingAsCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $cart = $this->createUserCart(
            $customer
        );

        $item = $cart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $response = $this->deleteJson(
            "/api/cart/items/{$item->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.items_count',
                0
            )
            ->assertJsonPath(
                'data.total_quantity',
                0
            )
            ->assertJsonPath(
                'data.subtotal',
                '0.00'
            );

        $this->assertDatabaseMissing(
            'cart_items',
            [
                'id' => $item->id,
            ]
        );

        $this->assertDatabaseHas(
            'carts',
            [
                'id' => $cart->id,
                'status' => 'active',
            ]
        );
    }

    public function test_customer_cannot_delete_item_from_another_cart(): void
    {
        $owner = $this->createCustomer();
        $attacker = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $ownerCart = $this->createUserCart(
            $owner
        );

        $item = $ownerCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $this->actingAs(
            $attacker,
            'web'
        );

        $this->deleteJson(
            "/api/cart/items/{$item->id}"
        )->assertNotFound();

        $this->assertDatabaseHas(
            'cart_items',
            [
                'id' => $item->id,
                'cart_id' => $ownerCart->id,
            ]
        );
    }

    public function test_customer_can_clear_cart_without_deleting_cart(): void
    {
        $customer = $this->actingAsCustomer();

        $firstVariant = $this
            ->createPurchasableVariant();

        $secondVariant = $this
            ->createPurchasableVariant();

        $cart = $this->createUserCart(
            $customer
        );

        $cart->items()->create([
            'product_variant_id' => $firstVariant->id,
            'quantity' => 2,
        ]);

        $cart->items()->create([
            'product_variant_id' => $secondVariant->id,
            'quantity' => 3,
        ]);

        $response = $this->deleteJson(
            '/api/cart'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $cart->id
            )
            ->assertJsonPath(
                'data.items_count',
                0
            )
            ->assertJsonPath(
                'data.total_quantity',
                0
            );

        $this->assertDatabaseHas(
            'carts',
            [
                'id' => $cart->id,
                'user_id' => $customer->id,
                'status' => 'active',
            ]
        );

        $this->assertSame(
            0,
            CartItem::query()
                ->where(
                    'cart_id',
                    $cart->id
                )
                ->count()
        );
    }

    public function test_normal_authenticated_cart_access_ignores_guest_cart_cookie(): void
    {
        $customer = $this->createCustomer();

        $guestCart = $this->createGuestCart();

        $userCart = $this->createUserCart(
            $customer
        );

        $this->actingAs(
            $customer,
            'web'
        );

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->getJson(
                '/api/cart'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $userCart->id
            );

        $guestCart->refresh();

        $this->assertNull(
            $guestCart->user_id
        );

        $this->assertSame(
            'active',
            $guestCart->status
        );
    }

    public function test_guest_cannot_call_merge_endpoint(): void
    {
        $this->postJson(
            '/api/cart/merge'
        )->assertUnauthorized();
    }

    public function test_guest_cart_becomes_user_cart_when_user_has_no_cart(): void
    {
        $customer = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $guestCart = $this->createGuestCart();

        $guestCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->postJson(
                '/api/cart/merge'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $guestCart->id
            )
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.items.0.quantity',
                3
            );

        $guestCart->refresh();

        $this->assertSame(
            $customer->id,
            $guestCart->user_id
        );

        $this->assertNull(
            $guestCart->guest_token
        );

        $this->assertNull(
            $guestCart->expires_at
        );

        $this->assertSame(
            'active',
            $guestCart->status
        );

        $response->assertCookieExpired(
            CartService::GUEST_COOKIE
        );
    }

    public function test_guest_cart_merges_into_existing_user_cart(): void
    {
        $customer = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 20,
                    'reserved_quantity' => 0,
                ]
            );

        $userCart = $this->createUserCart(
            $customer
        );

        $userCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ]);

        $guestCart = $this->createGuestCart();

        $guestCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 4,
            ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->postJson(
                '/api/cart/merge'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $userCart->id
            )
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.items.0.quantity',
                7
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'cart_id' => $userCart->id,
                'product_variant_id' => $variant->id,
                'quantity' => 7,
            ]
        );

        $guestCart->refresh();

        $this->assertSame(
            'converted',
            $guestCart->status
        );

        $this->assertNull(
            $guestCart->guest_token
        );

        $this->assertSame(
            0,
            CartItem::query()
                ->where(
                    'cart_id',
                    $guestCart->id
                )
                ->count()
        );
    }

    public function test_merge_caps_quantity_to_available_stock(): void
    {
        $customer = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant(
                inventoryAttributes: [
                    'on_hand_quantity' => 7,
                    'reserved_quantity' => 2,
                ]
            );

        $userCart = $this->createUserCart(
            $customer
        );

        $userCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 4,
            ]);

        $guestCart = $this->createGuestCart();

        $guestCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 4,
            ]);

        $this->actingAs(
            $customer,
            'web'
        );

        $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->postJson(
                '/api/cart/merge'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.items.0.quantity',
                5
            );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'cart_id' => $userCart->id,
                'product_variant_id' => $variant->id,
                'quantity' => 5,
            ]
        );
    }

    public function test_merge_skips_variant_that_is_no_longer_purchasable(): void
    {
        $customer = $this->createCustomer();

        $variant = $this
            ->createPurchasableVariant();

        $guestCart = $this->createGuestCart();

        $guestCart
            ->items()
            ->create([
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $variant->is_active = false;
        $variant->save();

        $this->actingAs(
            $customer,
            'web'
        );

        $response = $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->postJson(
                '/api/cart/merge'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.items_count',
                0
            );

        $userCart = Cart::query()
            ->where(
                'user_id',
                $customer->id
            )
            ->where(
                'status',
                'active'
            )
            ->firstOrFail();

        $this->assertSame(
            0,
            $userCart
                ->items()
                ->count()
        );

        $guestCart->refresh();

        $this->assertSame(
            'converted',
            $guestCart->status
        );
    }

    public function test_suspended_customer_cannot_merge_guest_cart(): void
    {
        $customer = $this->createCustomer([
            'status' => 'suspended',
        ]);

        $guestCart = $this->createGuestCart();

        $this->actingAs(
            $customer,
            'web'
        );

        $this
            ->withUnencryptedCookie(
                CartService::GUEST_COOKIE,
                $this->guestCookieValue(
                    $guestCart
                )
            )
            ->postJson(
                '/api/cart/merge'
            )
            ->assertForbidden();

        $guestCart->refresh();

        $this->assertNull(
            $guestCart->user_id
        );

        $this->assertSame(
            'active',
            $guestCart->status
        );
    }

    private function actingAsCustomer(): User
    {
        $customer = $this->createCustomer();

        $this->actingAs(
            $customer,
            'web'
        );

        return $customer;
    }

    private function createCustomer(
        array $attributes = []
    ): User {
        return User::factory()->create(
            array_merge(
                [
                    'role' => 'customer',
                    'status' => 'active',
                ],
                $attributes
            )
        );
    }

    private function createUserCart(
        User $user,
        array $attributes = []
    ): Cart {
        return $user
            ->carts()
            ->create(
                array_merge(
                    [
                        'status' => 'active',
                        'expires_at' => null,
                    ],
                    $attributes
                )
            );
    }

    private function createGuestCart(
        array $attributes = []
    ): Cart {
        return Cart::query()->create(
            array_merge(
                [
                    'guest_token' => (string) Str::uuid(),
                    'status' => 'active',
                    'expires_at' => now()
                        ->addDays(30),
                ],
                $attributes
            )
        );
    }

    private function guestCookieValue(
        Cart $cart
    ): string {
        return Crypt::encryptString(
            (string) $cart->guest_token
        );
    }

    private function createPurchasableVariant(
        array $productAttributes = [],
        array $variantAttributes = [],
        array $inventoryAttributes = [],
        bool $createInventory = true
    ): ProductVariant {
        $sequence = ++$this->sequence;

        $product = Product::query()->create(
            array_merge(
                [
                    'name' => "Cart Product {$sequence}",

                    'slug' => sprintf(
                        'cart-product-%d',
                        $sequence
                    ),

                    'sku' => sprintf(
                        'CART-PRODUCT-%06d',
                        $sequence
                    ),

                    'short_description' => null,
                    'description' => null,

                    'base_price' => 100,
                    'sale_price' => null,
                    'cost_price' => 50,

                    'status' => 'active',
                    'is_featured' => false,

                    'published_at' => now()
                        ->subMinute(),

                    'weight_grams' => null,
                    'length_cm' => null,
                    'width_cm' => null,
                    'height_cm' => null,

                    'seo_title' => null,
                    'seo_description' => null,
                ],
                $productAttributes
            )
        );

        $variant = $product
            ->variants()
            ->create(
                array_merge(
                    [
                        'name' => "Variant {$sequence}",

                        'sku' => sprintf(
                            'CART-VARIANT-%06d',
                            $sequence
                        ),

                        'barcode' => null,

                        'price' => 100,
                        'sale_price' => null,
                        'cost_price' => 50,

                        'weight_grams' => null,

                        'length_cm' => null,
                        'width_cm' => null,
                        'height_cm' => null,

                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                    $variantAttributes
                )
            );

        if ($createInventory) {
            $variant
                ->inventory()
                ->create(
                    array_merge(
                        [
                            'on_hand_quantity' => 100,
                            'reserved_quantity' => 0,
                            'low_stock_threshold' => 5,
                        ],
                        $inventoryAttributes
                    )
                );
        }

        return $variant->load([
            'product',
            'inventory',
        ]);
    }
}
