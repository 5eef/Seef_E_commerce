<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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

            'price' => [
                'base' => $this->base_price,
                'sale' => $this->sale_price,
            ],

            'is_featured' => $this->is_featured,

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

            'published_at' => $this->published_at?->toISOString(),

            'categories' => CategoryResource::collection(
                $this->whenLoaded('categories')
            ),

            'images' => ProductImageResource::collection(
                $this->whenLoaded('images')
            ),

            'variants' => ProductVariantResource::collection(
                $this->whenLoaded('variants')
            ),

            'options' => ProductOptionResource::collection(
                $this->whenLoaded('options')
            ),

            'reviews' => ReviewResource::collection(
                $this->whenLoaded('reviews')
            ),

            'review_count' => $this->whenCounted('reviews'),

            'rating_average' => $this->whenAggregated(
                'reviews',
                'rating',
                'avg'
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
