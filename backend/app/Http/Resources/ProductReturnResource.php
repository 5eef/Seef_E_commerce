<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'return_number' => $this->return_number,
            'order_id' => $this->order_id,

            'status' => $this->status,
            'reason' => $this->reason,

            'customer_note' => $this->customer_note,

            'refund_amount' => $this->refund_amount,

            'requested_at' => $this->requested_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),

            'items' => ReturnItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
