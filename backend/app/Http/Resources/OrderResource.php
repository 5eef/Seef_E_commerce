<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'order_number' => $this->order_number,

            'customer_name' => $this->customer_name,
            'email' => $this->email,
            'phone' => $this->phone,

            'status' => $this->status,
            'payment_status' => $this->payment_status,

            'currency' => $this->currency,

            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,

            'coupon_code' => $this->coupon_code,

            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,

            'customer_note' => $this->customer_note,

            'placed_at' => $this->placed_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'items_count' => $this->whenCounted('items'),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'shipments' => ShipmentResource::collection(
                $this->whenLoaded('shipments')
            ),

            'returns' => ProductReturnResource::collection(
                $this->whenLoaded('returns')
            ),

            'status_history' => $this->whenLoaded(
                'statusHistories',
                fn () => $this->statusHistories->map(fn ($history): array => [
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toISOString(),
                ])
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
