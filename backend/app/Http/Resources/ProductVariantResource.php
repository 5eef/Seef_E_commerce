<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'sku' => $this->sku,

            'price' => $this->price,
            'sale_price' => $this->sale_price,

            'weight_grams' => $this->weight_grams,

            'dimensions' => [
                'length_cm' => $this->length_cm,
                'width_cm' => $this->width_cm,
                'height_cm' => $this->height_cm,
            ],

            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,

            'option_values' => ProductOptionValueResource::collection(
                $this->whenLoaded('optionValues')
            ),

            'availability' => $this->whenLoaded(
                'inventory',
                function (): array {
                    if (! $this->inventory) {
                        return [
                            'in_stock' => false,
                        ];
                    }

                    $availableQuantity = max(
                        0,
                        $this->inventory->on_hand_quantity
                        - $this->inventory->reserved_quantity
                    );

                    return [
                        'in_stock' => $availableQuantity > 0,
                    ];
                }
            ),
        ];
    }
}
