<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => ProductResource::make(
                $this->whenLoaded('product')
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
