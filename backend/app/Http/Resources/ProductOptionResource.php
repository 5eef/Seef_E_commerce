<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'sort_order' => $this->sort_order,

            'values' => ProductOptionValueResource::collection(
                $this->whenLoaded('values')
            ),
        ];
    }
}
