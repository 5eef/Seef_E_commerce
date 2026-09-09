<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'method' => $this->method,
            'provider' => $this->provider,
            'status' => $this->status,

            'amount' => $this->amount,
            'currency' => $this->currency,

            'paid_at' => $this->paid_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'refunded_at' => $this->refunded_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
