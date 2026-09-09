<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        $query = User::query()
            ->where(
                'role',
                'customer'
            )
            ->withCount([
                'addresses',
                'carts',
                'orders',
                'reviews',
                'returns',
                'couponUsages',
            ]);

        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(
                function (
                    Builder $query
                ) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        if (
            array_key_exists(
                'status',
                $filters
            )
        ) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        match (
            $filters['sort'] ?? 'newest'
        ) {
            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name_asc' => $query
                ->orderBy('name')
                ->orderBy('id'),

            'name_desc' => $query
                ->orderByDesc('name')
                ->orderByDesc('id'),

            'email_asc' => $query
                ->orderBy('email')
                ->orderBy('id'),

            'email_desc' => $query
                ->orderByDesc('email')
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };

        return $query
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 25
                )
            )
            ->withQueryString();
    }

    public function findCustomerOrFail(
        int $customerId
    ): User {
        /** @var User $customer */
        $customer = User::query()
            ->where(
                'role',
                'customer'
            )
            ->withCount([
                'addresses',
                'carts',
                'orders',
                'reviews',
                'returns',
                'couponUsages',
            ])
            ->findOrFail(
                $customerId
            );

        return $customer;
    }

    public function updateStatus(
        User $customer,
        string $status
    ): User {
        return DB::transaction(
            function () use (
                $customer,
                $status
            ): User {
                /*
                 * status n'est volontairement pas
                 * mass-assignable dans User.
                 */
                $customer->status = $status;

                $customer->save();

                $customer->refresh();

                return $customer
                    ->loadCount([
                        'addresses',
                        'carts',
                        'orders',
                        'reviews',
                        'returns',
                        'couponUsages',
                    ]);
            }
        );
    }
}
