<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'order_item_id' => $this->order_item_id,

            'quantity' => $this->quantity,

            'reason' => $this->reason,
            'condition' => $this->condition,
            'resolution' => $this->resolution,

            'refund_amount' => $this->refund_amount,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
