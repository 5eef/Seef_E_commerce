<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Address $address): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $address->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Address $address): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $address->user_id === $user->id;
    }

    public function delete(User $user, Address $address): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $address->user_id === $user->id;
    }
}
