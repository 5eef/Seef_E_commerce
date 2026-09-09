<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,

            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,

            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,

            'weight_grams' => $this->weight_grams,

            'dimensions' => [
                'length_cm' => $this->length_cm,
                'width_cm' => $this->width_cm,
                'height_cm' => $this->height_cm,
            ],

            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,

            'option_values' => AdminProductOptionValueResource::collection(
                $this->whenLoaded('optionValues')
            ),

            'inventory' => $this->whenLoaded(
                'inventory',
                function (): ?array {
                    if ($this->inventory === null) {
                        return null;
                    }

                    $available = max(
                        0,
                        $this->inventory->on_hand_quantity
                            - $this->inventory->reserved_quantity
                    );

                    return [
                        'on_hand_quantity' => $this
                            ->inventory
                            ->on_hand_quantity,

                        'reserved_quantity' => $this
                            ->inventory
                            ->reserved_quantity,

                        'available_quantity' => $available,

                        'low_stock_threshold' => $this
                            ->inventory
                            ->low_stock_threshold,

                        'is_low_stock' => $available <= $this
                            ->inventory
                            ->low_stock_threshold,
                    ];
                }
            ),

            'cart_items_count' => $this->whenCounted(
                'cartItems'
            ),

            'order_items_count' => $this->whenCounted(
                'orderItems'
            ),

            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
