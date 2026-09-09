<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        /*
         * Pas de suppression physique des comptes.
         *
         * L'administration utilisera plutôt :
         * active
         * suspended
         * disabled
         *
         * Cela préserve l'historique commercial.
         */
        return false;
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin();
    }
}
