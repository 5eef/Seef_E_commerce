<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

        if ($this->filled('category')) {
            $data['category'] = Str::slug(
                (string) $this->input('category')
            );
        }

        if ($this->filled('sort')) {
            $data['sort'] = Str::lower(
                trim(
                    (string) $this->input('sort')
                )
            );
        }

        foreach (
            [
                'featured',
                'in_stock',
            ] as $field
        ) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value)) {
                $normalized = Str::lower(
                    trim($value)
                );

                if ($normalized === 'true') {
                    $value = true;
                } elseif ($normalized === 'false') {
                    $value = false;
                }
            }

            $data[$field] = $value;
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
                'max:120',
            ],

            'category' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',

                Rule::exists(
                    'categories',
                    'slug'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'min_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'lte:max_price',
            ],

            'max_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'gte:min_price',
            ],

            'featured' => [
                'sometimes',
                'boolean',
            ],

            'in_stock' => [
                'sometimes',
                'boolean',
            ],

            'sort' => [
                'sometimes',

                Rule::in([
                    'newest',
                    'price_asc',
                    'price_desc',
                    'name_asc',
                    'name_desc',
                ]),
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'between:1,48',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }
}
