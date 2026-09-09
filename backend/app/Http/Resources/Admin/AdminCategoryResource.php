<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,

            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,

            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,

            'products_count' => $this->whenCounted(
                'products'
            ),

            'children_count' => $this->whenCounted(
                'children'
            ),

            'parent' => $this->whenLoaded(
                'parent',
                fn () => $this->parent
                    ? [
                        'id' => $this->parent->id,
                        'name' => $this->parent->name,
                        'slug' => $this->parent->slug,
                    ]
                    : null
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
