<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && (
                $this->user()?->can(
                    'update',
                    $product
                ) ?? false
            );
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim(
                (string) $this->input('name')
            );
        }

        if ($this->has('slug')) {
            $data['slug'] = Str::slug(
                (string) $this->input('slug')
            );
        }

        if ($this->has('sku')) {
            $sku = $this->input('sku');

            $data['sku'] = $sku === null
                ? null
                : Str::upper(
                    trim((string) $sku)
                );
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return array_merge(
            $this->identityRules(),
            $this->pricingRules(),
            $this->attributeRules(),
            $this->protectedFieldRules(),
        );
    }

    private function identityRules(): array
    {
        $product = $this->route('product');

        $slugRule = Rule::unique(
            'products',
            'slug'
        );

        $skuRule = Rule::unique(
            'products',
            'sku'
        );

        if ($product instanceof Product) {
            $slugRule->ignore($product);
            $skuRule->ignore($product);
        }

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                $slugRule,
            ],

            'short_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:100000',
            ],

            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                $skuRule,
            ],
        ];
    }

    private function pricingRules(): array
    {
        return [

            'base_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'sale_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'cost_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
        ];
    }

    private function attributeRules(): array
    {
        return [

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
                'sometimes',
                'nullable',
                'date',
            ],

            'weight_grams' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'length_cm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'width_cm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'height_cm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'seo_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'seo_description' => [
                'sometimes',
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
        ];
    }

    private function protectedFieldRules(): array
    {
        return [

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if (! $product instanceof Product) {
                    return;
                }

                $basePrice = $this->has('base_price')
                    ? $this->input('base_price')
                    : $product->base_price;

                $salePrice = $this->has('sale_price')
                    ? $this->input('sale_price')
                    : $product->sale_price;

                if (
                    $salePrice !== null
                    && is_numeric($salePrice)
                    && is_numeric($basePrice)
                    && (float) $salePrice > (float) $basePrice
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'sale_price',
                            'The sale price must not be greater than the base price.'
                        );
                }
            },
        ];
    }
}
