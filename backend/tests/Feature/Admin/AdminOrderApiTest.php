<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_cannot_access_admin_orders(): void
    {
        $this->getJson(
            '/api/admin/orders'
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs(
            $customer,
            'web'
        );

        $order = $this->createOrder([
            'user_id' => $customer->id,
        ]);

        $this->getJson(
            '/api/admin/orders'
        )->assertForbidden();

        $this->getJson(
            "/api/admin/orders/{$order->id}"
        )->assertForbidden();

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',
            ]
        )->assertForbidden();
    }

    public function test_suspended_admin_cannot_access_admin_orders(): void
    {
        $admin = $this->createAdmin([
            'status' => 'suspended',
        ]);

        $this->actingAs(
            $admin,
            'web'
        );

        $order = $this->createOrder();

        $this->getJson(
            '/api/admin/orders'
        )->assertForbidden();

        $this->getJson(
            "/api/admin/orders/{$order->id}"
        )->assertForbidden();
    }

    public function test_admin_can_list_orders(): void
    {
        $this->actingAsAdmin();

        $first = $this->createOrder([
            'order_number' => 'ORD-ADMIN-0001',
            'customer_name' => 'First Customer',
        ]);

        $second = $this->createOrder([
            'order_number' => 'ORD-ADMIN-0002',
            'customer_name' => 'Second Customer',
        ]);

        $response = $this->getJson(
            '/api/admin/orders'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                2
            );

        $ids = collect(
            $response->json('data')
        )->pluck('id');

        $this->assertTrue(
            $ids->contains($first->id)
        );

        $this->assertTrue(
            $ids->contains($second->id)
        );
    }

    public function test_admin_can_search_and_filter_orders(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'email' => 'registered@example.test',
        ]);

        $target = $this->createOrder([
            'user_id' => $customer->id,
            'order_number' => 'ORD-SEARCH-9001',
            'customer_name' => 'Youssef Customer',
            'email' => 'youssef.order@example.test',
            'phone' => '+212600000001',
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $this->createOrder([
            'order_number' => 'ORD-SEARCH-9002',
            'customer_name' => 'Other Customer',
            'email' => 'other.order@example.test',
            'phone' => '+212600000002',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $response = $this->getJson(
            "/api/admin/orders?q=youssef&status=processing&payment_status=paid&user_id={$customer->id}"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $target->id
            )
            ->assertJsonPath(
                'data.0.order_number',
                'ORD-SEARCH-9001'
            )
            ->assertJsonPath(
                'data.0.status',
                'processing'
            )
            ->assertJsonPath(
                'data.0.payment_status',
                'paid'
            );
    }

    public function test_order_search_supports_order_number_email_and_phone(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'order_number' => 'SPECIAL-ORDER-7788',
            'customer_name' => 'Search Customer',
            'email' => 'special-search@example.test',
            'phone' => '+212677889900',
        ]);

        $this->getJson(
            '/api/admin/orders?q=7788'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $order->id
            );

        $this->getJson(
            '/api/admin/orders?q=special-search'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $order->id
            );

        $this->getJson(
            '/api/admin/orders?q=677889900'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $order->id
            );
    }

    public function test_admin_order_listing_supports_sorting_and_pagination(): void
    {
        $this->actingAsAdmin();

        $cheap = $this->createOrder([
            'order_number' => 'ORD-SORT-0001',
            'grand_total' => 100,
            'subtotal' => 100,
        ]);

        $this->createOrder([
            'order_number' => 'ORD-SORT-0002',
            'grand_total' => 900,
            'subtotal' => 900,
        ]);

        $response = $this->getJson(
            '/api/admin/orders?sort=total_asc&per_page=1&page=1'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $cheap->id
            )
            ->assertJsonPath(
                'meta.per_page',
                1
            )
            ->assertJsonPath(
                'meta.total',
                2
            );
    }

    public function test_admin_can_filter_orders_by_date_range(): void
    {
        $this->actingAsAdmin();

        $old = $this->createOrder([
            'order_number' => 'ORD-DATE-OLD',
        ]);

        $recent = $this->createOrder([
            'order_number' => 'ORD-DATE-RECENT',
        ]);

        Order::query()
            ->whereKey($old->id)
            ->update([
                'created_at' => now()
                    ->subDays(10),
            ]);

        Order::query()
            ->whereKey($recent->id)
            ->update([
                'created_at' => now()
                    ->subDay(),
            ]);

        $dateFrom = now()
            ->subDays(3)
            ->format('Y-m-d');

        $dateTo = now()
            ->format('Y-m-d');

        $response = $this->getJson(
            "/api/admin/orders?date_from={$dateFrom}&date_to={$dateTo}"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $recent->id
            );
    }

    public function test_invalid_order_filters_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->getJson(
            '/api/admin/orders?status=unknown'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $this->getJson(
            '/api/admin/orders?payment_status=unknown'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'payment_status'
            );

        $this->getJson(
            '/api/admin/orders?sort=random'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sort'
            );

        $this->getJson(
            '/api/admin/orders?per_page=500'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'per_page'
            );

        $this->getJson(
            '/api/admin/orders?date_from=2026-09-10&date_to=2026-09-01'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'date_to'
            );
    }

    public function test_admin_can_view_order_internal_details(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'name' => 'Registered Customer',
            'email' => 'registered-details@example.test',
            'phone' => '+212611111111',
        ]);

        $order = $this->createOrder([
            'user_id' => $customer->id,
            'order_number' => 'ORD-DETAIL-0001',
            'customer_name' => 'Order Snapshot Name',
            'email' => 'snapshot@example.test',
            'phone' => '+212622222222',
            'subtotal' => 500,
            'discount_total' => 50,
            'shipping_total' => 20,
            'tax_total' => 0,
            'grand_total' => 470,
            'admin_note' => 'Internal administration note',
            'customer_note' => 'Please call before delivery.',
        ]);

        $item = $this->createOrderItem(
            $order,
            [
                'product_name' => 'Internal Product',
                'sku' => 'INTERNAL-SKU-1',
                'unit_price' => 500,
                'quantity' => 1,
                'subtotal' => 500,
                'discount_total' => 50,
                'tax_total' => 0,
                'total' => 450,
                'unit_cost' => 275,
            ]
        );

        $response = $this->getJson(
            "/api/admin/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $order->id
            )
            ->assertJsonPath(
                'data.order_number',
                'ORD-DETAIL-0001'
            )
            ->assertJsonPath(
                'data.user_id',
                $customer->id
            )
            ->assertJsonPath(
                'data.customer.id',
                $customer->id
            )
            ->assertJsonPath(
                'data.customer.email',
                'registered-details@example.test'
            )
            ->assertJsonPath(
                'data.customer_name',
                'Order Snapshot Name'
            )
            ->assertJsonPath(
                'data.admin_note',
                'Internal administration note'
            )
            ->assertJsonPath(
                'data.items_count',
                1
            )
            ->assertJsonPath(
                'data.payments_count',
                0
            )
            ->assertJsonPath(
                'data.shipments_count',
                0
            )
            ->assertJsonPath(
                'data.returns_count',
                0
            )
            ->assertJsonPath(
                'data.items.0.id',
                $item->id
            )
            ->assertJsonPath(
                'data.items.0.unit_cost',
                '275.00'
            );
    }

    public function test_admin_can_view_guest_order(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'user_id' => null,
            'order_number' => 'ORD-GUEST-0001',
            'customer_name' => 'Guest Customer',
            'email' => 'guest@example.test',
            'phone' => '+212633333333',
        ]);

        $response = $this->getJson(
            "/api/admin/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $order->id
            )
            ->assertJsonPath(
                'data.user_id',
                null
            )
            ->assertJsonPath(
                'data.customer',
                null
            )
            ->assertJsonPath(
                'data.customer_name',
                'Guest Customer'
            );
    }

    public function test_missing_order_returns_not_found(): void
    {
        $this->actingAsAdmin();

        $this->getJson(
            '/api/admin/orders/999999999'
        )->assertNotFound();

        $this->patchJson(
            '/api/admin/orders/999999999/status',
            [
                'status' => 'confirmed',
            ]
        )->assertNotFound();
    }

    public function test_admin_can_confirm_pending_order_and_history_is_created(): void
    {
        $admin = $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        $response = $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',
                'note' => '  Payment verified manually.  ',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'confirmed'
            );

        $order->refresh();

        $this->assertSame(
            'confirmed',
            $order->status
        );

        $this->assertNotNull(
            $order->confirmed_at
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,
                'from_status' => 'pending',
                'to_status' => 'confirmed',
                'changed_by' => $admin->id,
                'note' => 'Payment verified manually.',
            ]
        );

        $this->assertSame(
            1,
            OrderStatusHistory::query()
                ->where(
                    'order_id',
                    $order->id
                )
                ->count()
        );
    }

    public function test_order_can_follow_complete_valid_status_workflow(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
        ]);

        foreach ([
            'confirmed',
            'processing',
            'shipped',
            'delivered',
            'completed',
        ] as $status) {
            $this->patchJson(
                "/api/admin/orders/{$order->id}/status",
                [
                    'status' => $status,
                ]
            )
                ->assertOk()
                ->assertJsonPath(
                    'data.status',
                    $status
                );
        }

        $order->refresh();

        $this->assertSame(
            'completed',
            $order->status
        );

        $this->assertNotNull(
            $order->confirmed_at
        );

        $this->assertNotNull(
            $order->completed_at
        );

        $this->assertNull(
            $order->cancelled_at
        );

        $this->assertSame(
            5,
            OrderStatusHistory::query()
                ->where(
                    'order_id',
                    $order->id
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,
                'from_status' => 'delivered',
                'to_status' => 'completed',
            ]
        );
    }

    public function test_admin_can_cancel_pending_order(): void
    {
        $admin = $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
            'cancelled_at' => null,
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'cancelled',
                'note' => 'Customer requested cancellation.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'cancelled'
            );

        $order->refresh();

        $this->assertSame(
            'cancelled',
            $order->status
        );

        $this->assertNotNull(
            $order->cancelled_at
        );

        $this->assertDatabaseHas(
            'order_status_histories',
            [
                'order_id' => $order->id,
                'from_status' => 'pending',
                'to_status' => 'cancelled',
                'changed_by' => $admin->id,
            ]
        );
    }

    public function test_invalid_order_status_transition_is_rejected(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'shipped',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $order->refresh();

        $this->assertSame(
            'pending',
            $order->status
        );

        $this->assertDatabaseCount(
            'order_status_histories',
            0
        );
    }

    public function test_same_order_status_is_rejected(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'pending',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $this->assertDatabaseCount(
            'order_status_histories',
            0
        );
    }

    public function test_cancelled_order_is_terminal(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $order->refresh();

        $this->assertSame(
            'cancelled',
            $order->status
        );
    }

    public function test_completed_order_is_terminal(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'processing',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $order->refresh();

        $this->assertSame(
            'completed',
            $order->status
        );
    }

    public function test_invalid_order_status_value_is_rejected(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'unknown-status',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $order->refresh();

        $this->assertSame(
            'pending',
            $order->status
        );
    }

    public function test_status_endpoint_rejects_financial_and_sensitive_fields(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();

        $order = $this->createOrder([
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'grand_total' => 500,
            'subtotal' => 500,
            'admin_note' => 'Original admin note',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',

                'payment_status' => 'paid',

                'subtotal' => 1,
                'grand_total' => 1,

                'user_id' => 999999,

                'customer_name' => 'Hacked Name',
                'email' => 'hacked@example.test',

                'admin_note' => 'Hacked note',

                'order_number' => 'HACKED-ORDER',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'payment_status',
                'subtotal',
                'grand_total',
                'user_id',
                'customer_name',
                'email',
                'admin_note',
                'order_number',
            ]);

        $order->refresh();

        $this->assertSame(
            'pending',
            $order->status
        );

        $this->assertSame(
            'pending',
            $order->payment_status
        );

        $this->assertSame(
            '500.00',
            $order->grand_total
        );

        $this->assertSame(
            '500.00',
            $order->subtotal
        );

        $this->assertSame(
            $customer->id,
            $order->user_id
        );

        $this->assertSame(
            'Original admin note',
            $order->admin_note
        );

        $this->assertDatabaseCount(
            'order_status_histories',
            0
        );
    }

    public function test_payment_status_is_not_changed_by_order_status_transition(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',
            ]
        )->assertOk();

        $order->refresh();

        $this->assertSame(
            'confirmed',
            $order->status
        );

        $this->assertSame(
            'pending',
            $order->payment_status
        );
    }

    public function test_order_status_history_is_returned_with_actor(): void
    {
        $admin = $this->actingAsAdmin();

        $order = $this->createOrder([
            'status' => 'pending',
        ]);

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'confirmed',
                'note' => 'Confirmed by administrator.',
            ]
        )->assertOk();

        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            [
                'status' => 'processing',
                'note' => 'Preparation started.',
            ]
        )->assertOk();

        $response = $this->getJson(
            "/api/admin/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.status_histories'
            )
            ->assertJsonPath(
                'data.status_histories.0.from_status',
                'pending'
            )
            ->assertJsonPath(
                'data.status_histories.0.to_status',
                'confirmed'
            )
            ->assertJsonPath(
                'data.status_histories.0.changed_by',
                $admin->id
            )
            ->assertJsonPath(
                'data.status_histories.0.actor.id',
                $admin->id
            )
            ->assertJsonPath(
                'data.status_histories.0.actor.email',
                $admin->email
            )
            ->assertJsonPath(
                'data.status_histories.0.note',
                'Confirmed by administrator.'
            )
            ->assertJsonPath(
                'data.status_histories.1.from_status',
                'confirmed'
            )
            ->assertJsonPath(
                'data.status_histories.1.to_status',
                'processing'
            );
    }

    public function test_order_cannot_be_physically_deleted_from_admin_api(): void
    {
        $this->actingAsAdmin();

        $order = $this->createOrder();

        $response = $this->deleteJson(
            "/api/admin/orders/{$order->id}"
        );

        $this->assertTrue(
            in_array(
                $response->status(),
                [
                    404,
                    405,
                ],
                true
            )
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ]
        );
    }

    public function test_admin_cannot_create_order_through_admin_orders_api(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(
            '/api/admin/orders',
            [
                'customer_name' => 'Illegal Admin Order',
            ]
        );

        $this->assertTrue(
            in_array(
                $response->status(),
                [
                    404,
                    405,
                ],
                true
            )
        );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->createAdmin();

        $this->actingAs(
            $admin,
            'web'
        );

        return $admin;
    }

    private function createAdmin(
        array $attributes = []
    ): User {
        return User::factory()->create(
            array_merge(
                [
                    'role' => 'admin',
                    'status' => 'active',
                ],
                $attributes
            )
        );
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

    private function createOrder(
        array $attributes = []
    ): Order {
        $sequence = ++$this->sequence;

        return Order::query()->create(
            array_merge(
                [
                    'order_number' => sprintf(
                        'ORD-TEST-%06d',
                        $sequence
                    ),

                    'user_id' => null,
                    'coupon_id' => null,

                    'customer_name' => "Customer {$sequence}",

                    'email' => sprintf(
                        'customer-%d@example.test',
                        $sequence
                    ),

                    'phone' => sprintf(
                        '+212600%06d',
                        $sequence
                    ),

                    'status' => 'pending',
                    'payment_status' => 'pending',

                    'currency' => 'MAD',

                    'subtotal' => 500,
                    'discount_total' => 0,
                    'shipping_total' => 0,
                    'tax_total' => 0,
                    'grand_total' => 500,

                    'coupon_code' => null,

                    'shipping_address' => [
                        'first_name' => 'Test',
                        'last_name' => 'Customer',
                        'phone' => sprintf(
                            '+212600%06d',
                            $sequence
                        ),
                        'address_line_1' => '1 Test Street',
                        'address_line_2' => null,
                        'city' => 'Fes',
                        'region' => 'Fes-Meknes',
                        'postal_code' => '30000',
                        'country_code' => 'MA',
                    ],

                    'billing_address' => null,

                    'customer_note' => null,
                    'admin_note' => null,

                    'placed_at' => now(),

                    'confirmed_at' => null,
                    'cancelled_at' => null,
                    'completed_at' => null,
                ],
                $attributes
            )
        );
    }

    private function createOrderItem(
        Order $order,
        array $attributes = []
    ): OrderItem {
        $sequence = ++$this->sequence;

        return $order
            ->items()
            ->create(
                array_merge(
                    [
                        'product_id' => null,
                        'product_variant_id' => null,

                        'product_name' => "Product {$sequence}",
                        'variant_name' => null,

                        'sku' => sprintf(
                            'ORDER-SKU-%06d',
                            $sequence
                        ),

                        'option_values' => null,

                        'unit_price' => 100,
                        'quantity' => 1,

                        'subtotal' => 100,
                        'discount_total' => 0,
                        'tax_total' => 0,
                        'total' => 100,

                        'unit_cost' => 60,
                    ],
                    $attributes
                )
            );
    }
}
