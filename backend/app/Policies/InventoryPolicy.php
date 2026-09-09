<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        /*
         * L'inventaire sera créé automatiquement avec
         * la variante produit et non via un endpoint
         * CRUD indépendant.
         */
        return false;
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        /*
         * Le stock est lié en 1:1 à une variante.
         * On interdit la suppression manuelle.
         */
        return false;
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }
}
