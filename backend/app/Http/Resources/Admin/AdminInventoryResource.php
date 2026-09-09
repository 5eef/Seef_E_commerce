<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'on_hand_quantity' => $this->on_hand_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => max(0, $this->on_hand_quantity - $this->reserved_quantity),
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->on_hand_quantity <= $this->low_stock_threshold,
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant->id,
                'name' => $this->variant->name,
                'sku' => $this->variant->sku,
                'product' => $this->variant->relationLoaded('product') ? [
                    'id' => $this->variant->product?->id,
                    'name' => $this->variant->product?->name,
                ] : null,
            ]),
            'movements' => $this->whenLoaded('movements', fn () => $this->movements->map(fn ($movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'reason' => $movement->reason,
                'created_at' => $movement->created_at?->toISOString(),
            ])),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
