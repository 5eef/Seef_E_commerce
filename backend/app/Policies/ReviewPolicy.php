<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Review $review): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin()
            || $review->user_id === $user->id
            || $review->status === 'approved';
    }

    public function create(User $user): bool
    {
        /*
         * Les avis doivent représenter des clients.
         * Un administrateur ne publie pas de faux avis client.
         */
        return $user->isActive()
            && ! $user->isAdmin();
    }

    public function update(User $user, Review $review): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $review->user_id === $user->id
            && $review->status === 'pending';
    }

    public function delete(User $user, Review $review): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $review->user_id === $user->id
            && $review->status === 'pending';
    }

    public function restore(User $user, Review $review): bool
    {
        return false;
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return false;
    }
}
