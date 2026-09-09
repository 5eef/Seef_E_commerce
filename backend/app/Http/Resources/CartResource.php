<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : collect();

        $totalQuantity = $items->sum(
            fn ($item): int => (int) $item->quantity
        );

        $subtotalCents = $items->sum(
            function ($item): int {
                if ($item->line_total === null) {
                    return 0;
                }

                [$whole, $decimal] = array_pad(
                    explode(
                        '.',
                        (string) $item->line_total,
                        2
                    ),
                    2,
                    '0'
                );

                return (
                    (int) $whole
                    * 100
                ) + (int) str_pad(
                    substr(
                        $decimal,
                        0,
                        2
                    ),
                    2,
                    '0'
                );
            }
        );

        return [
            'id' => $this->id,

            'status' => $this->status,

            'currency' => 'MAD',

            'items_count' => $items->count(),

            'total_quantity' => $totalQuantity,

            'subtotal' => sprintf(
                '%d.%02d',
                intdiv(
                    $subtotalCents,
                    100
                ),
                $subtotalCents % 100
            ),

            'expires_at' => $this->expires_at?->toISOString(),

            /*
             * guest_token n'est volontairement
             * jamais exposé.
             */
            'items' => CartItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
