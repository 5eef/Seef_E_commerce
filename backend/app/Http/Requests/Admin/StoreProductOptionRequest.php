<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'product_options',
                    'name'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'product_id',
                            $product->id
                        )
                ),
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}
