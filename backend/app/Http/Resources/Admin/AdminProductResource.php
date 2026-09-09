<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,

            'short_description' => $this->short_description,
            'description' => $this->description,

            'base_price' => $this->base_price,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,

            'status' => $this->status,
            'is_featured' => $this->is_featured,

            'published_at' => $this->published_at?->toISOString(),

            'weight_grams' => $this->weight_grams,

            'dimensions' => [
                'length_cm' => $this->length_cm,
                'width_cm' => $this->width_cm,
                'height_cm' => $this->height_cm,
            ],

            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
            ],

            'categories' => $this->whenLoaded(
                'categories',
                fn () => $this->categories
                    ->map(
                        fn ($category): array => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'is_active' => $category->is_active,
                        ]
                    )
                    ->values()
            ),

            'variants_count' => $this->whenCounted(
                'variants'
            ),

            'images_count' => $this->whenCounted(
                'images'
            ),

            'reviews_count' => $this->whenCounted(
                'reviews'
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
