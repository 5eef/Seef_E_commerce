<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,

            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'sku' => $this->sku,

            'option_values' => $this->option_values,

            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,

            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
