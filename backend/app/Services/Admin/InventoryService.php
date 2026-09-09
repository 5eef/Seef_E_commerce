<?php

namespace App\Services\Admin;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Inventory::query()
            ->with(['variant.product'])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->whereHas('variant', fn ($variantQuery) => $variantQuery
                ->where('sku', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))))
            ->when(($filters['low_stock'] ?? false), fn ($query) => $query->whereColumn('on_hand_quantity', '<=', 'low_stock_threshold'))
            ->orderBy('on_hand_quantity')
            ->orderBy('id')
            ->paginate(min(100, max(1, (int) ($filters['per_page'] ?? 25))))
            ->withQueryString();
    }

    /** @param array<string, mixed> $data */
    public function adjust(Inventory $inventory, User $actor, array $data): Inventory
    {
        DB::transaction(function () use ($inventory, $actor, $data): void {
            $locked = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $before = $locked->on_hand_quantity;
            $after = match ($data['operation']) {
                'increase' => $before + $data['quantity'],
                'decrease' => $before - $data['quantity'],
                'set' => $data['quantity'],
            };

            if ($after < 0 || $after < $locked->reserved_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['The resulting stock cannot be negative or below reserved stock.'],
                ]);
            }

            $locked->on_hand_quantity = $after;
            if (array_key_exists('low_stock_threshold', $data)) {
                $locked->low_stock_threshold = $data['low_stock_threshold'];
            }
            $locked->save();

            $locked->movements()->create([
                'balance' => 'on_hand',
                'type' => 'adjustment',
                'quantity' => $after - $before,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $data['reason'],
                'user_id' => $actor->id,
            ]);
        });

        return $inventory->refresh()->load(['variant.product', 'movements' => fn ($query) => $query->latest()->limit(20)]);
    }
}
