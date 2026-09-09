<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,

            'type' => $this->type,
            'value' => $this->value,

            'minimum_order_amount' => $this->minimum_order_amount,
            'maximum_discount_amount' => $this->maximum_discount_amount,

            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),

            'is_active' => $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
