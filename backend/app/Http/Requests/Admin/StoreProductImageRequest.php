<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class StoreProductImageRequest extends FormRequest
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

        if ($this->has('alt_text')) {
            $value = trim(
                (string) $this->input(
                    'alt_text'
                )
            );

            $data['alt_text'] = $value !== ''
                ? $value
                : null;
        }

        if ($this->has('is_primary')) {
            $value = $this->input(
                'is_primary'
            );

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

            $data['is_primary'] = $value;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'prohibited',
            ],

            'disk' => [
                'prohibited',
            ],

            'path' => [
                'prohibited',
            ],

            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'alt_text' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
