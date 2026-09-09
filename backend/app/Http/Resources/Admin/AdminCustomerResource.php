<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,

            'role' => $this->role,
            'status' => $this->status,

            'email_verified_at' => $this
                ->email_verified_at
                ?->toISOString(),

            'last_login_at' => $this
                ->last_login_at
                ?->toISOString(),

            'addresses_count' => $this->whenCounted(
                'addresses'
            ),

            'carts_count' => $this->whenCounted(
                'carts'
            ),

            'orders_count' => $this->whenCounted(
                'orders'
            ),

            'reviews_count' => $this->whenCounted(
                'reviews'
            ),

            'returns_count' => $this->whenCounted(
                'returns'
            ),

            'coupon_usages_count' => $this->whenCounted(
                'couponUsages'
            ),

            'created_at' => $this
                ->created_at
                ?->toISOString(),

            'updated_at' => $this
                ->updated_at
                ?->toISOString(),
        ];
    }
}
