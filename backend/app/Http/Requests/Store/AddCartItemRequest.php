<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
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
            'product_variant_id' => [
                'required',
                'integer',

                Rule::exists(
                    'product_variants',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                ),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:99',
            ],

            /*
             * Champs contrôlés exclusivement
             * par le serveur.
             */
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
