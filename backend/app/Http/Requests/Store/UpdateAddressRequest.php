<?php

namespace App\Http\Requests\Store;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');

        return $address instanceof Address
            && (
                $this->user()?->can(
                    'update',
                    $address
                ) ?? false
            );
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (
            [
                'first_name',
                'last_name',
                'phone',
            ] as $field
        ) {
            if ($this->has($field)) {
                $data[$field] = trim(
                    (string) $this->input($field)
                );
            }
        }

        if ($this->has('country_code')) {
            $countryCode = $this->input(
                'country_code'
            );

            $data['country_code'] =
                $countryCode === null
                    ? null
                    : Str::upper(
                        trim(
                            (string) $countryCode
                        )
                    );
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'label' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'first_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'last_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:30',
            ],

            'address_line_1' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'address_line_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'sometimes',
                'required',
                'string',
                'max:120',
            ],

            'region' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
            ],

            'postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'country_code' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[A-Z]{2}$/',
            ],

            'delivery_instructions' => [
                'sometimes',
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
