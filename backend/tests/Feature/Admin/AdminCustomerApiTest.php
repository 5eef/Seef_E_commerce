<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_customers(): void
    {
        $this->getJson(
            '/api/admin/customers'
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_customers(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs(
            $customer,
            'web'
        );

        $this->getJson(
            '/api/admin/customers'
        )->assertForbidden();
    }

    public function test_suspended_admin_cannot_access_customers(): void
    {
        $admin = $this->createAdmin([
            'status' => 'suspended',
        ]);

        $this->actingAs(
            $admin,
            'web'
        );

        $this->getJson(
            '/api/admin/customers'
        )->assertForbidden();
    }

    public function test_admin_listing_contains_only_customers(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'name' => 'Real Customer',
            'email' => 'customer@example.test',
        ]);

        $admin = $this->createAdmin([
            'name' => 'Second Admin',
            'email' => 'admin-two@example.test',
        ]);

        $response = $this->getJson(
            '/api/admin/customers'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $customer->id
            )
            ->assertJsonPath(
                'data.0.role',
                'customer'
            );

        $ids = collect(
            $response->json('data')
        )->pluck('id');

        $this->assertFalse(
            $ids->contains(
                $admin->id
            )
        );
    }

    public function test_admin_can_search_and_filter_customers(): void
    {
        $this->actingAsAdmin();

        $target = $this->createCustomer([
            'name' => 'Youssef Client',
            'email' => 'youssef.customer@example.test',
            'phone' => '+212600000001',
            'status' => 'suspended',
        ]);

        $this->createCustomer([
            'name' => 'Other Customer',
            'email' => 'other@example.test',
            'phone' => '+212600000002',
            'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/admin/customers?q=youssef&status=suspended'
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
                'data.0.status',
                'suspended'
            );
    }

    public function test_admin_customer_listing_supports_sorting_and_pagination(): void
    {
        $this->actingAsAdmin();

        $alpha = $this->createCustomer([
            'name' => 'Alpha Customer',
            'email' => 'alpha@example.test',
        ]);

        $this->createCustomer([
            'name' => 'Bravo Customer',
            'email' => 'bravo@example.test',
        ]);

        $response = $this->getJson(
            '/api/admin/customers?sort=name_asc&per_page=1'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $alpha->id
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

    public function test_invalid_customer_filters_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->getJson(
            '/api/admin/customers?status=deleted'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $this->getJson(
            '/api/admin/customers?sort=random'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'sort'
            );

        $this->getJson(
            '/api/admin/customers?per_page=500'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'per_page'
            );
    }

    public function test_admin_can_view_customer_internal_profile(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'name' => 'Customer Details',
            'email' => 'details@example.test',
            'phone' => '+212611111111',
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/admin/customers/{$customer->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $customer->id
            )
            ->assertJsonPath(
                'data.name',
                'Customer Details'
            )
            ->assertJsonPath(
                'data.email',
                'details@example.test'
            )
            ->assertJsonPath(
                'data.phone',
                '+212611111111'
            )
            ->assertJsonPath(
                'data.role',
                'customer'
            )
            ->assertJsonPath(
                'data.status',
                'active'
            )
            ->assertJsonPath(
                'data.addresses_count',
                0
            )
            ->assertJsonPath(
                'data.carts_count',
                0
            )
            ->assertJsonPath(
                'data.orders_count',
                0
            )
            ->assertJsonPath(
                'data.reviews_count',
                0
            )
            ->assertJsonPath(
                'data.returns_count',
                0
            )
            ->assertJsonPath(
                'data.coupon_usages_count',
                0
            )
            ->assertJsonMissingPath(
                'data.password'
            )
            ->assertJsonMissingPath(
                'data.remember_token'
            );
    }

    public function test_admin_account_cannot_be_accessed_through_customer_endpoint(): void
    {
        $this->actingAsAdmin();

        $targetAdmin = $this->createAdmin([
            'email' => 'target-admin@example.test',
        ]);

        $this->getJson(
            "/api/admin/customers/{$targetAdmin->id}"
        )->assertNotFound();

        $this->patchJson(
            "/api/admin/customers/{$targetAdmin->id}/status",
            [
                'status' => 'disabled',
            ]
        )->assertNotFound();

        $targetAdmin->refresh();

        $this->assertSame(
            'active',
            $targetAdmin->status
        );
    }

    public function test_admin_can_suspend_customer(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'status' => 'active',
        ]);

        $this->patchJson(
            "/api/admin/customers/{$customer->id}/status",
            [
                'status' => 'suspended',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $customer->id
            )
            ->assertJsonPath(
                'data.status',
                'suspended'
            );

        $customer->refresh();

        $this->assertSame(
            'suspended',
            $customer->status
        );

        $this->assertSame(
            'customer',
            $customer->role
        );
    }

    public function test_admin_can_disable_and_reactivate_customer(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'status' => 'active',
        ]);

        $this->patchJson(
            "/api/admin/customers/{$customer->id}/status",
            [
                'status' => 'disabled',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'disabled'
            );

        $this->patchJson(
            "/api/admin/customers/{$customer->id}/status",
            [
                'status' => 'active',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'active'
            );

        $customer->refresh();

        $this->assertSame(
            'active',
            $customer->status
        );
    }

    public function test_invalid_customer_status_is_rejected(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();

        $this->patchJson(
            "/api/admin/customers/{$customer->id}/status",
            [
                'status' => 'banned',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'status'
            );

        $customer->refresh();

        $this->assertSame(
            'active',
            $customer->status
        );
    }

    public function test_customer_status_endpoint_rejects_sensitive_fields(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer([
            'name' => 'Protected Customer',
            'email' => 'protected@example.test',
            'status' => 'active',
        ]);

        $originalPassword = $customer->password;

        $this->patchJson(
            "/api/admin/customers/{$customer->id}/status",
            [
                'status' => 'disabled',
                'role' => 'admin',
                'password' => 'hacked-password',
                'email' => 'hacked@example.test',
                'name' => 'Hacked',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
                'password',
                'email',
                'name',
            ]);

        $customer->refresh();

        $this->assertSame(
            'customer',
            $customer->role
        );

        $this->assertSame(
            'active',
            $customer->status
        );

        $this->assertSame(
            'Protected Customer',
            $customer->name
        );

        $this->assertSame(
            'protected@example.test',
            $customer->email
        );

        $this->assertSame(
            $originalPassword,
            $customer->password
        );
    }

    public function test_customer_accounts_are_not_physically_deleted_by_admin_api(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();

        $response = $this->deleteJson(
            "/api/admin/customers/{$customer->id}"
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
            'users',
            [
                'id' => $customer->id,
                'role' => 'customer',
            ]
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
}
