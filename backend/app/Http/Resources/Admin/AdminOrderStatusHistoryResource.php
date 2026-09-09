<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'from_status' => $this->from_status,
            'to_status' => $this->to_status,

            'changed_by' => $this->changed_by,

            'actor' => $this->whenLoaded(
                'changedBy',
                fn (): ?array => $this->changedBy === null
                    ? null
                    : [
                        'id' => $this->changedBy->id,
                        'name' => $this->changedBy->name,
                        'email' => $this->changedBy->email,
                    ]
            ),

            'note' => $this->note,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
