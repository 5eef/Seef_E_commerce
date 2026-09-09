<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user === null
            || $user->isActive();
    }

    public function rules(): array
    {
        return [
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:99',
            ],

            'product_variant_id' => ['prohibited'],

            'cart_id' => ['prohibited'],
            'guest_token' => ['prohibited'],
            'user_id' => ['prohibited'],

            'unit_price' => ['prohibited'],
            'price' => ['prohibited'],
            'sale_price' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'total' => ['prohibited'],

            'reserved_quantity' => ['prohibited'],
            'on_hand_quantity' => ['prohibited'],
        ];
    }
}
