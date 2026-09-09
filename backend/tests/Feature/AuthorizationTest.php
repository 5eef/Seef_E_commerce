<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_laravel_discovers_core_policies(): void
    {
        $this->assertInstanceOf(
            ProductPolicy::class,
            Gate::getPolicyFor(Product::class)
        );

        $this->assertInstanceOf(
            CategoryPolicy::class,
            Gate::getPolicyFor(Category::class)
        );

        $this->assertInstanceOf(
            CouponPolicy::class,
            Gate::getPolicyFor(Coupon::class)
        );

        $this->assertInstanceOf(
            InventoryPolicy::class,
            Gate::getPolicyFor(Inventory::class)
        );

        $this->assertInstanceOf(
            OrderPolicy::class,
            Gate::getPolicyFor(Order::class)
        );

        $this->assertInstanceOf(
            ReviewPolicy::class,
            Gate::getPolicyFor(Review::class)
        );

        $this->assertInstanceOf(
            AddressPolicy::class,
            Gate::getPolicyFor(Address::class)
        );

        $this->assertInstanceOf(
            UserPolicy::class,
            Gate::getPolicyFor(User::class)
        );
    }

    public function test_product_management_is_active_admin_only(): void
    {
        $customer = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            2,
            'admin',
            'active'
        );

        $suspendedAdmin = $this->makeUser(
            3,
            'admin',
            'suspended'
        );

        $product = new Product;

        $policy = new ProductPolicy;

        $this->assertFalse(
            $policy->viewAny($customer)
        );

        $this->assertFalse(
            $policy->create($customer)
        );

        $this->assertTrue(
            $policy->viewAny($admin)
        );

        $this->assertTrue(
            $policy->create($admin)
        );

        $this->assertTrue(
            $policy->update($admin, $product)
        );

        $this->assertTrue(
            $policy->delete($admin, $product)
        );

        $this->assertTrue(
            $policy->restore($admin, $product)
        );

        $this->assertFalse(
            $policy->forceDelete($admin, $product)
        );

        $this->assertFalse(
            $policy->create($suspendedAdmin)
        );

        $this->assertFalse(
            $policy->update(
                $suspendedAdmin,
                $product
            )
        );
    }

    public function test_category_management_is_active_admin_only(): void
    {
        $customer = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            2,
            'admin',
            'active'
        );

        $disabledAdmin = $this->makeUser(
            3,
            'admin',
            'disabled'
        );

        $category = new Category;

        $policy = new CategoryPolicy;

        $this->assertFalse(
            $policy->create($customer)
        );

        $this->assertTrue(
            $policy->create($admin)
        );

        $this->assertTrue(
            $policy->update($admin, $category)
        );

        $this->assertTrue(
            $policy->delete($admin, $category)
        );

        $this->assertFalse(
            $policy->update(
                $disabledAdmin,
                $category
            )
        );
    }

    public function test_coupon_management_is_active_admin_only(): void
    {
        $customer = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            2,
            'admin',
            'active'
        );

        $suspendedAdmin = $this->makeUser(
            3,
            'admin',
            'suspended'
        );

        $coupon = new Coupon;

        $policy = new CouponPolicy;

        $this->assertFalse(
            $policy->create($customer)
        );

        $this->assertTrue(
            $policy->create($admin)
        );

        $this->assertTrue(
            $policy->update($admin, $coupon)
        );

        $this->assertTrue(
            $policy->delete($admin, $coupon)
        );

        $this->assertFalse(
            $policy->update(
                $suspendedAdmin,
                $coupon
            )
        );

        $this->assertFalse(
            $policy->forceDelete($admin, $coupon)
        );
    }

    public function test_inventory_management_is_admin_only(): void
    {
        $customer = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            2,
            'admin',
            'active'
        );

        $inventory = new Inventory;

        $policy = new InventoryPolicy;

        $this->assertFalse(
            $policy->viewAny($customer)
        );

        $this->assertTrue(
            $policy->viewAny($admin)
        );

        $this->assertTrue(
            $policy->update($admin, $inventory)
        );

        $this->assertFalse(
            $policy->create($admin)
        );

        $this->assertFalse(
            $policy->delete($admin, $inventory)
        );
    }

    public function test_order_policy_protects_customer_orders(): void
    {
        $owner = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $otherCustomer = $this->makeUser(
            2,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            3,
            'admin',
            'active'
        );

        $suspendedOwner = $this->makeUser(
            1,
            'customer',
            'suspended'
        );

        $order = new Order;
        $order->forceFill([
            'user_id' => 1,
        ]);

        $policy = new OrderPolicy;

        $this->assertTrue(
            $policy->view($owner, $order)
        );

        $this->assertFalse(
            $policy->view(
                $otherCustomer,
                $order
            )
        );

        $this->assertTrue(
            $policy->view($admin, $order)
        );

        $this->assertTrue(
            $policy->update($admin, $order)
        );

        $this->assertFalse(
            $policy->update($owner, $order)
        );

        $this->assertFalse(
            $policy->view(
                $suspendedOwner,
                $order
            )
        );

        $this->assertFalse(
            $policy->delete($admin, $order)
        );
    }

    public function test_address_policy_prevents_idor_between_customers(): void
    {
        $owner = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $otherCustomer = $this->makeUser(
            2,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            3,
            'admin',
            'active'
        );

        $address = new Address;
        $address->forceFill([
            'user_id' => 1,
        ]);

        $policy = new AddressPolicy;

        $this->assertTrue(
            $policy->view($owner, $address)
        );

        $this->assertTrue(
            $policy->update($owner, $address)
        );

        $this->assertTrue(
            $policy->delete($owner, $address)
        );

        $this->assertFalse(
            $policy->view(
                $otherCustomer,
                $address
            )
        );

        $this->assertFalse(
            $policy->update(
                $otherCustomer,
                $address
            )
        );

        $this->assertFalse(
            $policy->delete(
                $otherCustomer,
                $address
            )
        );

        $this->assertTrue(
            $policy->view($admin, $address)
        );
    }

    public function test_review_policy_respects_owner_admin_and_moderation_state(): void
    {
        $owner = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $otherCustomer = $this->makeUser(
            2,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            3,
            'admin',
            'active'
        );

        $pendingReview = new Review;
        $pendingReview->forceFill([
            'user_id' => 1,
            'status' => 'pending',
        ]);

        $approvedReview = new Review;
        $approvedReview->forceFill([
            'user_id' => 1,
            'status' => 'approved',
        ]);

        $policy = new ReviewPolicy;

        $this->assertTrue(
            $policy->create($owner)
        );

        $this->assertFalse(
            $policy->create($admin)
        );

        $this->assertTrue(
            $policy->update(
                $owner,
                $pendingReview
            )
        );

        $this->assertFalse(
            $policy->update(
                $otherCustomer,
                $pendingReview
            )
        );

        $this->assertFalse(
            $policy->update(
                $owner,
                $approvedReview
            )
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $approvedReview
            )
        );

        $this->assertTrue(
            $policy->view(
                $otherCustomer,
                $approvedReview
            )
        );

        $this->assertFalse(
            $policy->view(
                $otherCustomer,
                $pendingReview
            )
        );
    }

    public function test_user_policy_allows_admin_management_without_physical_deletion(): void
    {
        $customer = $this->makeUser(
            1,
            'customer',
            'active'
        );

        $otherCustomer = $this->makeUser(
            2,
            'customer',
            'active'
        );

        $admin = $this->makeUser(
            3,
            'admin',
            'active'
        );

        $disabledAdmin = $this->makeUser(
            4,
            'admin',
            'disabled'
        );

        $policy = new UserPolicy;

        $this->assertTrue(
            $policy->view(
                $customer,
                $customer
            )
        );

        $this->assertFalse(
            $policy->view(
                $customer,
                $otherCustomer
            )
        );

        $this->assertTrue(
            $policy->viewAny($admin)
        );

        $this->assertTrue(
            $policy->view(
                $admin,
                $customer
            )
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $customer
            )
        );

        $this->assertFalse(
            $policy->viewAny($disabledAdmin)
        );

        $this->assertFalse(
            $policy->delete(
                $admin,
                $customer
            )
        );
    }

    private function makeUser(
        int $id,
        string $role,
        string $status
    ): User {
        $user = new User;

        $user->forceFill([
            'id' => $id,
            'name' => 'Test User',
            'email' => "user{$id}@example.com",
            'role' => $role,
            'status' => $status,
        ]);

        return $user;
    }
}
