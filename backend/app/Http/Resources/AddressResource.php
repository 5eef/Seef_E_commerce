<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'label' => $this->label,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,

            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,

            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,

            'delivery_instructions' => $this->delivery_instructions,

            'is_default_shipping' => $this->is_default_shipping,
            'is_default_billing' => $this->is_default_billing,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
