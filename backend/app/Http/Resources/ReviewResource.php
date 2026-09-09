<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,

            'status' => $this->status,
            'is_verified_purchase' => $this->is_verified_purchase,

            'user' => $this->whenLoaded(
                'user',
                fn () => $this->user
                    ? [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ]
                    : null
            ),

            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
