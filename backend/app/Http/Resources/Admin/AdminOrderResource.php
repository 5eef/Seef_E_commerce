<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductReturnResource;
use App\Http\Resources\ShipmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'order_number' => $this->order_number,

            'user_id' => $this->user_id,
            'coupon_id' => $this->coupon_id,

            'customer' => $this->whenLoaded(
                'user',
                fn (): ?array => $this->user === null
                    ? null
                    : [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'phone' => $this->user->phone,
                        'status' => $this->user->status,
                    ]
            ),

            /*
             * Snapshots enregistrés au moment
             * de la commande.
             */
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

            /*
             * Information interne non exposée
             * dans OrderResource public.
             */
            'admin_note' => $this->admin_note,

            'placed_at' => $this->placed_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'items_count' => $this->whenCounted('items'),
            'payments_count' => $this->whenCounted('payments'),
            'shipments_count' => $this->whenCounted('shipments'),
            'returns_count' => $this->whenCounted('returns'),

            'items' => AdminOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'status_histories' => AdminOrderStatusHistoryResource::collection(
                $this->whenLoaded('statusHistories')
            ),

            /*
             * On conserve pour l'instant les Resources
             * sécurisés publics pour ces domaines.
             * Les informations internes Payment seront
             * traitées au module 13.
             */
            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'shipments' => ShipmentResource::collection(
                $this->whenLoaded('shipments')
            ),

            'returns' => ProductReturnResource::collection(
                $this->whenLoaded('returns')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
