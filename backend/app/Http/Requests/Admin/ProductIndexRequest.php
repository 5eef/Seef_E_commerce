<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'viewAny',
            Product::class
        ) ?? false;
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

        if ($this->has('status')) {
            $data['status'] = Str::lower(
                trim(
                    (string) $this->input('status')
                )
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

        if ($this->has('featured')) {
            $value = $this->input('featured');

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

            $data['featured'] = $value;
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

            'status' => [
                'sometimes',
                'nullable',

                Rule::in([
                    'draft',
                    'active',
                    'archived',
                ]),
            ],

            'featured' => [
                'sometimes',
                'boolean',
            ],

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(
                    'categories',
                    'id'
                ),
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
                    'newest',
                    'oldest',
                    'name_asc',
                    'name_desc',
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
