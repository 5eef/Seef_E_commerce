<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceApiCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlist_requires_authentication_and_prevents_duplicates(): void
    {
        $product = $this->productWithStock()['product'];

        $this->getJson('/api/wishlist')->assertUnauthorized();

        $customer = $this->customer();
        $this->actingAs($customer, 'web')
            ->postJson('/api/wishlist/items', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.items.0.product.id', $product->id);

        $this->postJson('/api/wishlist/items', ['product_id' => $product->id])->assertOk();

        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_addresses_are_private_and_default_flags_are_unique(): void
    {
        $customer = $this->customer();
        $other = $this->customer();
        $payload = $this->addressPayload();

        $first = $customer->addresses()->create($payload + ['is_default_shipping' => true]);

        $this->actingAs($customer, 'web')
            ->postJson('/api/addresses', $payload + ['label' => 'Work', 'is_default_shipping' => true])
            ->assertCreated()
            ->assertJsonPath('data.is_default_shipping', true);

        $this->assertFalse($first->refresh()->is_default_shipping);

        $foreignAddress = $other->addresses()->create($payload);

        $this->getJson('/api/addresses/'.$foreignAddress->id)->assertForbidden();
    }

    public function test_checkout_calculates_totals_decrements_stock_and_cannot_be_repeated(): void
    {
        $customer = $this->customer();
        ['variant' => $variant, 'inventory' => $inventory] = $this->productWithStock(8, '125.50');
        $cart = $customer->carts()->create(['status' => 'active']);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

        $response = $this->actingAs($customer, 'web')->postJson('/api/checkout', $this->checkoutPayload() + [
            'subtotal' => '0.01',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('subtotal');

        $response = $this->postJson('/api/checkout', $this->checkoutPayload());
        $response->assertCreated()
            ->assertJsonPath('data.subtotal', '251.00')
            ->assertJsonPath('data.grand_total', '251.00')
            ->assertJsonPath('data.payments.0.status', 'pending');

        $this->assertSame(6, $inventory->refresh()->on_hand_quantity);
        $this->assertDatabaseHas('inventory_movements', ['inventory_id' => $inventory->id, 'type' => 'sale', 'quantity' => -2]);
        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'status' => 'converted']);

        $this->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');
    }

    public function test_checkout_applies_coupon_limits_from_server(): void
    {
        $customer = $this->customer();
        ['variant' => $variant] = $this->productWithStock(5, '200.00');
        $customer->carts()->create(['status' => 'active'])->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        Coupon::query()->create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => '10.00',
            'usage_limit' => 5,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'web')
            ->postJson('/api/checkout', $this->checkoutPayload() + ['coupon_code' => 'save10'])
            ->assertCreated()
            ->assertJsonPath('data.discount_total', '20.00')
            ->assertJsonPath('data.grand_total', '180.00');

        $this->assertDatabaseHas('coupon_usages', ['user_id' => $customer->id, 'discount_amount' => '20.00']);
    }

    public function test_customer_cannot_read_another_customers_order(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $order = $this->order($owner);

        $this->actingAs($other, 'web')->getJson('/api/orders/'.$order->id)->assertForbidden();
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_inventory_adjustment_is_audited_and_cannot_go_negative(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        ['inventory' => $inventory] = $this->productWithStock(5);

        $this->actingAs($admin, 'web')->patchJson('/api/admin/inventory/'.$inventory->id, [
            'operation' => 'decrease',
            'quantity' => 2,
            'reason' => 'Damaged units',
        ])->assertOk()->assertJsonPath('data.on_hand_quantity', 3);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'quantity_before' => 5,
            'quantity_after' => 3,
            'reason' => 'Damaged units',
        ]);

        $this->patchJson('/api/admin/inventory/'.$inventory->id, [
            'operation' => 'decrease',
            'quantity' => 4,
            'reason' => 'Invalid adjustment',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_review_requires_a_delivered_purchase(): void
    {
        $customer = $this->customer();
        ['product' => $product, 'variant' => $variant] = $this->productWithStock();

        $this->actingAs($customer, 'web')->postJson('/api/products/'.$product->slug.'/reviews', [
            'rating' => 5,
            'body' => 'Excellent product.',
        ])->assertUnprocessable()->assertJsonValidationErrors('order_item_id');

        $order = $this->order($customer, 'delivered');
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'sku' => $variant->sku,
            'unit_price' => '100.00',
            'quantity' => 1,
            'subtotal' => '100.00',
            'total' => '100.00',
        ]);

        $this->postJson('/api/products/'.$product->slug.'/reviews', [
            'rating' => 5,
            'body' => 'Excellent product.',
            'order_item_id' => $item->id,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.is_verified_purchase', true);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'status' => 'active']);
    }

    /** @return array{product: Product, variant: ProductVariant, inventory: Inventory} */
    private function productWithStock(int $stock = 10, string $price = '100.00'): array
    {
        $suffix = fake()->unique()->numerify('######');
        $product = Product::query()->create([
            'name' => 'Product '.$suffix,
            'slug' => 'product-'.$suffix,
            'sku' => 'P-'.$suffix,
            'base_price' => $price,
            'status' => 'active',
            'published_at' => now()->subMinute(),
        ]);
        $variant = $product->variants()->create([
            'name' => 'Default',
            'sku' => 'V-'.$suffix,
            'price' => $price,
            'is_active' => true,
        ]);
        $inventory = $variant->inventory()->create([
            'on_hand_quantity' => $stock,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
        ]);

        return compact('product', 'variant', 'inventory');
    }

    /** @return array<string, mixed> */
    private function addressPayload(): array
    {
        return [
            'label' => 'Home',
            'first_name' => 'Youssef',
            'last_name' => 'Boughioul',
            'phone' => '+212600000000',
            'address_line_1' => '1 Main Street',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country_code' => 'MA',
        ];
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(): array
    {
        return [
            'email' => 'customer@example.com',
            'phone' => '+212600000000',
            'shipping_address' => $this->addressPayload(),
            'billing_same_as_shipping' => true,
            'payment_method' => 'cod',
        ];
    }

    private function order(User $user, string $status = 'pending'): Order
    {
        return $user->orders()->create([
            'order_number' => 'SEEF-'.fake()->unique()->numerify('########'),
            'customer_name' => $user->name,
            'email' => $user->email,
            'phone' => '+212600000000',
            'status' => $status,
            'payment_status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => '100.00',
            'grand_total' => '100.00',
            'shipping_address' => $this->addressPayload(),
            'placed_at' => now(),
        ]);
    }
}
