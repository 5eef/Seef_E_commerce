<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function restore(User $user, Coupon $coupon): bool
    {
        return false;
    }

    public function forceDelete(User $user, Coupon $coupon): bool
    {
        return false;
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }
}
