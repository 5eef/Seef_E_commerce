<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }
}
