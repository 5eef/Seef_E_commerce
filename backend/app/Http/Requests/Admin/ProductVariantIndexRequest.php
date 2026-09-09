<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class ProductVariantIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can(
                'view',
                $product
            ) ?? false);
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

        if ($this->has('sort')) {
            $data['sort'] = Str::lower(
                trim(
                    (string) $this->input('sort')
                )
            );
        }

        if ($this->has('trashed')) {
            $data['trashed'] = Str::lower(
                trim(
                    (string) $this->input('trashed')
                )
            );
        }

        if ($this->has('active')) {
            $value = $this->input('active');

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

            $data['active'] = $value;
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

            'active' => [
                'sometimes',
                'boolean',
            ],

            'trashed' => [
                'sometimes',
                Rule::in([
                    'without',
                    'with',
                    'only',
                ]),
            ],

            'sort' => [
                'sometimes',
                Rule::in([
                    'sort_order',
                    'newest',
                    'oldest',
                    'name_asc',
                    'name_desc',
                    'sku_asc',
                    'sku_desc',
                    'price_asc',
                    'price_desc',
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
