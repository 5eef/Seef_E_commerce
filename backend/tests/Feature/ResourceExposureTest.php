<?php

namespace Tests\Feature;

use App\Http\Resources\CartResource;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductReturnResource;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ResourceExposureTest extends TestCase
{
    private function request(): Request
    {
        return Request::create('/', 'GET');
    }

    public function test_product_does_not_expose_cost_price(): void
    {
        $product = new Product;

        $product->forceFill([
            'id' => 1,
            'name' => 'Product',
            'slug' => 'product',
            'sku' => 'PRODUCT-001',
            'base_price' => '299.00',
            'sale_price' => '249.00',
            'cost_price' => '100.00',
            'is_featured' => true,
        ]);

        $data = (new ProductResource($product))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'cost_price',
            $data
        );
    }

    public function test_product_variant_does_not_expose_internal_cost_or_barcode(): void
    {
        $variant = new ProductVariant;

        $variant->forceFill([
            'id' => 1,
            'name' => 'Black',
            'sku' => 'BLACK-001',
            'barcode' => '123456789',
            'price' => '299.00',
            'sale_price' => '249.00',
            'cost_price' => '100.00',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $data = (new ProductVariantResource($variant))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'cost_price',
            $data
        );

        $this->assertArrayNotHasKey(
            'barcode',
            $data
        );
    }

    public function test_cart_does_not_expose_guest_token(): void
    {
        $cart = new Cart;

        $cart->forceFill([
            'id' => 1,
            'guest_token' => 'PRIVATE-GUEST-TOKEN',
            'status' => 'active',
        ]);

        $cart->setRelation(
            'items',
            new Collection
        );

        $data = (new CartResource($cart))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'guest_token',
            $data
        );
    }

    public function test_order_item_does_not_expose_unit_cost(): void
    {
        $item = new OrderItem;

        $item->forceFill([
            'id' => 1,
            'product_id' => 1,
            'product_variant_id' => 1,

            'product_name' => 'Product',
            'variant_name' => 'Default',
            'sku' => 'TEST-001',

            'option_values' => [],

            'unit_price' => '299.00',
            'quantity' => 1,

            'subtotal' => '299.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'total' => '299.00',

            'unit_cost' => '100.00',
        ]);

        $data = (new OrderItemResource($item))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'unit_cost',
            $data
        );
    }

    public function test_order_does_not_expose_admin_note(): void
    {
        $order = new Order;

        $order->forceFill([
            'id' => 1,
            'order_number' => 'ORD-001',

            'customer_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '0600000000',

            'status' => 'pending',
            'payment_status' => 'pending',

            'currency' => 'MAD',

            'subtotal' => '299.00',
            'discount_total' => '0.00',
            'shipping_total' => '0.00',
            'tax_total' => '0.00',
            'grand_total' => '299.00',

            'shipping_address' => [
                'city' => 'Sefrou',
            ],

            'customer_note' => 'Customer message',
            'admin_note' => 'INTERNAL SECRET',
        ]);

        $order->setRelation(
            'items',
            new Collection
        );

        $data = (new OrderResource($order))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'admin_note',
            $data
        );
    }

    public function test_payment_does_not_expose_provider_secrets(): void
    {
        $payment = new Payment;

        $payment->forceFill([
            'id' => 1,
            'method' => 'card',
            'provider' => 'provider',
            'provider_reference' => 'PRIVATE-REFERENCE',
            'status' => 'failed',
            'amount' => '299.00',
            'currency' => 'MAD',
            'failure_reason' => 'PRIVATE FAILURE DETAIL',
            'metadata' => [
                'secret' => 'private',
            ],
        ]);

        $data = (new PaymentResource($payment))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'provider_reference',
            $data
        );

        $this->assertArrayNotHasKey(
            'failure_reason',
            $data
        );

        $this->assertArrayNotHasKey(
            'metadata',
            $data
        );
    }

    public function test_return_does_not_expose_admin_note(): void
    {
        $return = new ProductReturn;

        $return->forceFill([
            'id' => 1,
            'return_number' => 'RET-001',
            'order_id' => 1,
            'status' => 'requested',
            'reason' => 'Damaged product',
            'customer_note' => 'Customer note',
            'admin_note' => 'PRIVATE ADMIN NOTE',
            'refund_amount' => '0.00',
        ]);

        $return->setRelation(
            'items',
            new Collection
        );

        $data = (new ProductReturnResource($return))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'admin_note',
            $data
        );
    }

    public function test_review_does_not_expose_customer_email(): void
    {
        $user = new User;

        $user->forceFill([
            'id' => 1,
            'name' => 'Customer',
            'email' => 'private@example.com',
        ]);

        $review = new Review;

        $review->forceFill([
            'id' => 1,
            'rating' => 5,
            'title' => 'Good',
            'body' => 'Good product',
            'status' => 'approved',
            'is_verified_purchase' => true,
        ]);

        $review->setRelation(
            'user',
            $user
        );

        $data = (new ReviewResource($review))
            ->toArray($this->request());

        $this->assertSame(
            'Customer',
            $data['user']['name']
        );

        $this->assertArrayNotHasKey(
            'email',
            $data['user']
        );
    }

    public function test_user_resource_does_not_expose_authentication_secrets(): void
    {
        $user = new User;

        $user->forceFill([
            'id' => 1,
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'HASHED PASSWORD',
            'remember_token' => 'PRIVATE TOKEN',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $data = (new UserResource($user))
            ->toArray($this->request());

        $this->assertArrayNotHasKey(
            'password',
            $data
        );

        $this->assertArrayNotHasKey(
            'remember_token',
            $data
        );
    }
}
