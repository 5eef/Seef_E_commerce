<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'carrier' => $this->carrier,
            'tracking_number' => $this->tracking_number,

            'status' => $this->status,

            'shipping_cost' => $this->shipping_cost,

            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
