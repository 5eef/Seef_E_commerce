<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variant = $this->variant;

        return [
            'id' => $this->id,

            'quantity' => $this->quantity,

            'unit_price' => $this->unit_price,

            'line_total' => $this->line_total,

            'available_quantity' => $this->available_quantity,

            'is_available' => $this->is_available,

            'product' => $variant?->product === null
                ? null
                : [
                    'id' => $variant->product->id,
                    'name' => $variant->product->name,
                    'slug' => $variant->product->slug,
                ],

            'variant' => $variant === null
                ? null
                : new ProductVariantResource(
                    $variant
                ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
