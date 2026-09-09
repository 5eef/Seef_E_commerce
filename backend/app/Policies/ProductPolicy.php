<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        /*
         * Les produits peuvent être référencés par :
         * - commandes
         * - avis
         * - wishlists
         * - inventaire
         *
         * On interdit donc volontairement la suppression
         * physique définitive.
         */
        return false;
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }
}
