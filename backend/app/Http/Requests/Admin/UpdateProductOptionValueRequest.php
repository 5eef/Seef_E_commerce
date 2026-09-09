<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductOptionValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');
        $option = $this->route('option');
        $value = $this->route('value');

        return $product instanceof Product
            && $option instanceof ProductOption
            && $value instanceof ProductOptionValue
            && $option->product_id === $product->id
            && $value->product_option_id === $option->id
            && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        /** @var ProductOption $option */
        $option = $this->route('option');

        /** @var ProductOptionValue $value */
        $value = $this->route('value');

        return [
            'value' => [
                'sometimes',
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'product_option_values',
                    'value'
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'product_option_id',
                                $option->id
                            )
                    )
                    ->ignore($value->id),
            ],

            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}
