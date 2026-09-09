<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pending' => [
            'confirmed',
            'cancelled',
        ],

        'confirmed' => [
            'processing',
            'cancelled',
        ],

        'processing' => [
            'shipped',
            'cancelled',
        ],

        'shipped' => [
            'delivered',
        ],

        'delivered' => [
            'completed',
        ],

        'cancelled' => [],

        'completed' => [],
    ];

    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        $query = Order::query()
            ->withCount([
                'items',
                'payments',
                'shipments',
                'returns',
            ]);

        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(
                function (
                    Builder $query
                ) use ($search): void {
                    $query
                        ->where(
                            'order_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'customer_name',
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

        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (! empty($filters['payment_status'])) {
            $query->where(
                'payment_status',
                $filters['payment_status']
            );
        }

        if (! empty($filters['user_id'])) {
            $query->where(
                'user_id',
                $filters['user_id']
            );
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['date_from']
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['date_to']
            );
        }

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'total_asc' => $query
                ->orderBy('grand_total')
                ->orderBy('id'),

            'total_desc' => $query
                ->orderByDesc('grand_total')
                ->orderByDesc('id'),

            'order_number_asc' => $query
                ->orderBy('order_number')
                ->orderBy('id'),

            'order_number_desc' => $query
                ->orderByDesc('order_number')
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

    public function loadForAdmin(
        Order $order
    ): Order {
        return $order
            ->load([
                'user',
                'items',
                'payments',
                'shipments',
                'returns',
                'statusHistories' => fn ($query) => $query
                    ->with('changedBy')
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->loadCount([
                'items',
                'payments',
                'shipments',
                'returns',
            ]);
    }

    public function updateStatus(
        Order $order,
        User $actor,
        string $newStatus,
        ?string $note = null
    ): Order {
        DB::transaction(
            function () use (
                $order,
                $actor,
                $newStatus,
                $note
            ): void {
                /** @var Order $lockedOrder */
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldStatus = $lockedOrder->status;

                if ($oldStatus === $newStatus) {
                    throw ValidationException::withMessages([
                        'status' => [
                            'The order already has this status.',
                        ],
                    ]);
                }

                $allowedTransitions = self::TRANSITIONS[
                    $oldStatus
                ] ?? [];

                if (! in_array(
                    $newStatus,
                    $allowedTransitions,
                    true
                )) {
                    throw ValidationException::withMessages([
                        'status' => [
                            "Transition from {$oldStatus} to {$newStatus} is not allowed.",
                        ],
                    ]);
                }

                $lockedOrder->status = $newStatus;

                if (
                    $newStatus === 'confirmed'
                    && $lockedOrder->confirmed_at === null
                ) {
                    $lockedOrder->confirmed_at = now();
                }

                if (
                    $newStatus === 'cancelled'
                    && $lockedOrder->cancelled_at === null
                ) {
                    $lockedOrder->cancelled_at = now();
                }

                if (
                    $newStatus === 'completed'
                    && $lockedOrder->completed_at === null
                ) {
                    $lockedOrder->completed_at = now();
                }

                $lockedOrder->save();

                $lockedOrder
                    ->statusHistories()
                    ->create([
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'changed_by' => $actor->id,
                        'note' => $note,
                    ]);
            }
        );

        $order->refresh();

        return $this->loadForAdmin(
            $order
        );
    }
}
