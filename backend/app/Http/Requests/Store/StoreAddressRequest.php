<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive()
            ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [
            'first_name' => trim(
                (string) $this->input('first_name')
            ),

            'last_name' => trim(
                (string) $this->input('last_name')
            ),

            'phone' => trim(
                (string) $this->input('phone')
            ),
        ];

        if ($this->filled('country_code')) {
            $data['country_code'] = Str::upper(
                trim(
                    (string) $this->input(
                        'country_code'
                    )
                )
            );
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'label' => [
                'nullable',
                'string',
                'max:50',
            ],

            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'address_line_1' => [
                'required',
                'string',
                'max:255',
            ],

            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'required',
                'string',
                'max:120',
            ],

            'region' => [
                'nullable',
                'string',
                'max:120',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],

            'country_code' => [
                'sometimes',
                'string',
                'regex:/^[A-Z]{2}$/',
            ],

            'delivery_instructions' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_default_shipping' => [
                'sometimes',
                'boolean',
            ],

            'is_default_billing' => [
                'sometimes',
                'boolean',
            ],

            'user_id' => [
                'prohibited',
            ],
        ];
    }
}
