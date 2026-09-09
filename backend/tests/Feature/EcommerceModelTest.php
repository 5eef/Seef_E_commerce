<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_domain_relations_and_casts_are_consistent(): void
    {
        $user = User::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'Password123',
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'TEST50',
            'name' => 'Test Coupon',
            'type' => 'fixed',
            'value' => '50.00',
            'minimum_order_amount' => '200.00',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'PRODUCT-001',
            'base_price' => '299.00',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $variant = $product->variants()->create([
            'name' => 'Default',
            'sku' => 'VARIANT-001',
            'price' => '299.00',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-001',

            'user_id' => $user->id,
            'coupon_id' => $coupon->id,

            'customer_name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0600000000',

            'status' => 'pending',
            'payment_status' => 'pending',

            'currency' => 'MAD',

            'subtotal' => '299.00',
            'discount_total' => '50.00',
            'shipping_total' => '50.00',
            'tax_total' => '0.00',
            'grand_total' => '299.00',

            'coupon_code' => 'TEST50',

            'shipping_address' => [
                'city' => 'Sefrou',
                'country_code' => 'MA',
            ],

            'placed_at' => now(),
        ]);

        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,

            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,

            'option_values' => [],

            'unit_price' => '299.00',
            'quantity' => 1,

            'subtotal' => '299.00',
            'discount_total' => '50.00',
            'tax_total' => '0.00',
            'total' => '249.00',

            'unit_cost' => '150.00',
        ]);

        $payment = $order->payments()->create([
            'method' => 'cod',
            'status' => 'pending',
            'amount' => '299.00',
            'currency' => 'MAD',
        ]);

        $shipment = $order->shipments()->create([
            'status' => 'pending',
            'shipping_cost' => '50.00',
        ]);

        $couponUsage = $order->couponUsage()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'discount_amount' => '50.00',
            'used_at' => now(),
        ]);

        $productReturn = $order->returns()->create([
            'return_number' => 'RET-TEST-001',
            'user_id' => $user->id,
            'status' => 'requested',
            'reason' => 'Test return',
            'refund_amount' => '249.00',
            'requested_at' => now(),
        ]);

        $returnItem = $productReturn->items()->create([
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'reason' => 'Test',
            'resolution' => 'refund',
            'refund_amount' => '249.00',
            'restock' => true,
        ]);

        $order->refresh()->load([
            'user',
            'coupon',
            'items',
            'payments',
            'shipments',
            'couponUsage',
            'returns.items',
        ]);

        $this->assertTrue($order->user->is($user));
        $this->assertTrue($order->coupon->is($coupon));

        $this->assertCount(1, $order->items);
        $this->assertCount(1, $order->payments);
        $this->assertCount(1, $order->shipments);
        $this->assertCount(1, $order->returns);

        $this->assertTrue(
            $order->couponUsage->is($couponUsage)
        );

        $this->assertSame(
            [
                'city' => 'Sefrou',
                'country_code' => 'MA',
            ],
            $order->shipping_address
        );

        $this->assertSame('299.00', $order->grand_total);
        $this->assertSame('299.00', $payment->amount);
        $this->assertSame('50.00', $shipment->shipping_cost);

        $this->assertTrue($returnItem->restock);
    }
}
