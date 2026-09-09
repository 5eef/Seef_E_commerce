<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Order $order): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }

    public function delete(User $user, Order $order): bool
    {
        /*
         * Une commande fait partie de l'historique commercial.
         * Elle ne doit pas être supprimée physiquement via CRUD.
         */
        return false;
    }

    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
