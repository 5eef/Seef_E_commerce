<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');
        $option = $this->route('option');

        return $product instanceof Product
            && $option instanceof ProductOption
            && $option->product_id === $product->id
            && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        /** @var ProductOption $option */
        $option = $this->route('option');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'product_options',
                    'name'
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'product_id',
                                $product->id
                            )
                    )
                    ->ignore($option->id),
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}
