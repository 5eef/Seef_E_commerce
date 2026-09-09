<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->isActive()
            && $user->isAdmin();
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('q')) {
            $data['q'] = trim(
                (string) $this->input('q')
            );
        }

        foreach ([
            'status',
            'payment_status',
            'sort',
        ] as $field) {
            if ($this->has($field)) {
                $data[$field] = Str::lower(
                    trim(
                        (string) $this->input($field)
                    )
                );
            }
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'q' => [
                'sometimes',
                'nullable',
                'string',
                'min:2',
                'max:150',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                    'completed',
                ]),
            ],

            'payment_status' => [
                'sometimes',
                Rule::in([
                    'pending',
                    'paid',
                    'failed',
                    'refunded',
                    'partially_refunded',
                ]),
            ],

            'user_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'date_from' => [
                'sometimes',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'sometimes',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

            'sort' => [
                'sometimes',
                Rule::in([
                    'newest',
                    'oldest',
                    'total_asc',
                    'total_desc',
                    'order_number_asc',
                    'order_number_desc',
                ]),
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'between:1,100',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }
}
