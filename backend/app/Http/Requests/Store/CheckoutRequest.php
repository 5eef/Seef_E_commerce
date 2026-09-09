<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        /*
         * Checkout invité autorisé.
         *
         * Si un utilisateur est connecté,
         * son compte doit être actif.
         */
        return $user === null
            || $user->isActive();
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [
            'email' => Str::lower(
                trim(
                    (string) $this->input('email')
                )
            ),

            'phone' => trim(
                (string) $this->input('phone')
            ),
        ];

        if ($this->filled('coupon_code')) {
            $data['coupon_code'] = Str::upper(
                trim(
                    (string) $this->input(
                        'coupon_code'
                    )
                )
            );
        }

        $shippingAddress = $this->input(
            'shipping_address'
        );

        if (is_array($shippingAddress)) {
            if (
                isset(
                    $shippingAddress[
                        'country_code'
                    ]
                )
            ) {
                $shippingAddress[
                    'country_code'
                ] = Str::upper(
                    trim(
                        (string) $shippingAddress[
                            'country_code'
                        ]
                    )
                );
            }

            $data['shipping_address'] =
                $shippingAddress;
        }

        $billingAddress = $this->input(
            'billing_address'
        );

        if (is_array($billingAddress)) {
            if (
                isset(
                    $billingAddress[
                        'country_code'
                    ]
                )
            ) {
                $billingAddress[
                    'country_code'
                ] = Str::upper(
                    trim(
                        (string) $billingAddress[
                            'country_code'
                        ]
                    )
                );
            }

            $data['billing_address'] =
                $billingAddress;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'shipping_address' => [
                'required',
                'array',
            ],

            'shipping_address.first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address.last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address.phone' => [
                'required',
                'string',
                'max:30',
            ],

            'shipping_address.address_line_1' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address.address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_address.city' => [
                'required',
                'string',
                'max:120',
            ],

            'shipping_address.region' => [
                'nullable',
                'string',
                'max:120',
            ],

            'shipping_address.postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],

            'shipping_address.country_code' => [
                'required',
                'string',
                'regex:/^[A-Z]{2}$/',
            ],

            'shipping_address.delivery_instructions' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'billing_same_as_shipping' => [
                'sometimes',
                'boolean',
            ],

            'billing_address' => [
                'nullable',

                Rule::requiredIf(
                    fn (): bool => $this->has(
                        'billing_same_as_shipping'
                    )
                        && ! $this->boolean(
                            'billing_same_as_shipping'
                        )
                ),

                'array',
            ],

            'billing_address.first_name' => [
                'required_with:billing_address',
                'string',
                'max:255',
            ],

            'billing_address.last_name' => [
                'required_with:billing_address',
                'string',
                'max:255',
            ],

            'billing_address.phone' => [
                'required_with:billing_address',
                'string',
                'max:30',
            ],

            'billing_address.address_line_1' => [
                'required_with:billing_address',
                'string',
                'max:255',
            ],

            'billing_address.address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'billing_address.city' => [
                'required_with:billing_address',
                'string',
                'max:120',
            ],

            'billing_address.region' => [
                'nullable',
                'string',
                'max:120',
            ],

            'billing_address.postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],

            'billing_address.country_code' => [
                'required_with:billing_address',
                'string',
                'regex:/^[A-Z]{2}$/',
            ],

            'payment_method' => [
                'required',

                Rule::in([
                    'cod',
                    'card',
                    'bank_transfer',
                ]),
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'customer_note' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
             * Tous ces champs doivent être
             * calculés exclusivement côté serveur.
             */

            'order_number' => [
                'prohibited',
            ],

            'user_id' => [
                'prohibited',
            ],

            'status' => [
                'prohibited',
            ],

            'payment_status' => [
                'prohibited',
            ],

            'currency' => [
                'prohibited',
            ],

            'subtotal' => [
                'prohibited',
            ],

            'discount_total' => [
                'prohibited',
            ],

            'shipping_total' => [
                'prohibited',
            ],

            'tax_total' => [
                'prohibited',
            ],

            'grand_total' => [
                'prohibited',
            ],

            'admin_note' => [
                'prohibited',
            ],
        ];
    }
}
