<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Product::class
        ) ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $name = trim(
            (string) $this->input('name')
        );

        $slug = $this->filled('slug')
            ? Str::slug(
                (string) $this->input('slug')
            )
            : Str::slug($name);

        $data = [
            'name' => $name,
            'slug' => $slug,
        ];

        if ($this->filled('sku')) {
            $data['sku'] = Str::upper(
                trim(
                    (string) $this->input('sku')
                )
            );
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                'unique:products,slug',
            ],

            'short_description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:100000',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'lte:base_price',
            ],

            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'draft',
                    'active',
                    'archived',
                ]),
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],

            'weight_grams' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'length_cm' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'width_cm' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'height_cm' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seo_description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'category_ids' => [
                'sometimes',
                'array',
                'max:50',
            ],

            'category_ids.*' => [
                'integer',
                'distinct:strict',
                'exists:categories,id',
            ],

            'id' => [
                'prohibited',
            ],

            'deleted_at' => [
                'prohibited',
            ],

            'created_at' => [
                'prohibited',
            ],

            'updated_at' => [
                'prohibited',
            ],
        ];
    }
}
