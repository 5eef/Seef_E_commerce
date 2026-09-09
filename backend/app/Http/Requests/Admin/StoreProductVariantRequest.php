<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can(
                'update',
                $product
            ) ?? false);
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $value = trim(
                (string) $this->input('name')
            );

            $data['name'] = $value !== ''
                ? $value
                : null;
        }

        if ($this->has('sku')) {
            $data['sku'] = Str::upper(
                trim(
                    (string) $this->input('sku')
                )
            );
        }

        if ($this->has('barcode')) {
            $value = trim(
                (string) $this->input('barcode')
            );

            $data['barcode'] = $value !== ''
                ? $value
                : null;
        }

        if ($this->has('is_active')) {
            $value = $this->input('is_active');

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

            $data['is_active'] = $value;
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

            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:120',
                Rule::unique(
                    'product_variants',
                    'sku'
                ),
            ],

            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                Rule::unique(
                    'product_variants',
                    'barcode'
                ),
            ],

            'price' => [
                'sometimes',
                'nullable',
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
            ],

            'width_cm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'height_cm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'option_value_ids' => [
                'sometimes',
                'array',
                'max:50',
            ],

            'option_value_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(
                    'product_option_values',
                    'id'
                ),
            ],

            /*
             * L'inventaire est géré par le serveur.
             * Sa modification viendra à l'étape 10.
             */
            'on_hand_quantity' => [
                'prohibited',
            ],

            'reserved_quantity' => [
                'prohibited',
            ],

            'low_stock_threshold' => [
                'prohibited',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateSalePrice(
                    $validator
                );

                $this->validateOptionValues(
                    $validator
                );
            },
        ];
    }

    private function validateSalePrice(
        Validator $validator
    ): void {
        if (
            ! $this->filled(
                'sale_price'
            )
        ) {
            return;
        }

        if (
            $validator
                ->errors()
                ->has('sale_price')
            || $validator
                ->errors()
                ->has('price')
        ) {
            return;
        }

        /** @var Product $product */
        $product = $this->route('product');

        $regularPrice = $this->filled('price')
            ? (float) $this->input('price')
            : (float) $product->base_price;

        if (
            (float) $this->input('sale_price')
            > $regularPrice
        ) {
            $validator
                ->errors()
                ->add(
                    'sale_price',
                    'The sale price must not be greater than the effective regular price.'
                );
        }
    }

    private function validateOptionValues(
        Validator $validator
    ): void {
        if (
            ! $this->has(
                'option_value_ids'
            )
        ) {
            return;
        }

        if (
            $validator
                ->errors()
                ->has('option_value_ids')
            || $validator
                ->errors()
                ->has('option_value_ids.*')
        ) {
            return;
        }

        /** @var Product $product */
        $product = $this->route('product');

        $ids = collect(
            $this->input(
                'option_value_ids',
                []
            )
        )
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $values = DB::table(
            'product_option_values'
        )
            ->join(
                'product_options',
                'product_options.id',
                '=',
                'product_option_values.product_option_id'
            )
            ->whereIn(
                'product_option_values.id',
                $ids->all()
            )
            ->select([
                'product_option_values.id',
                'product_option_values.product_option_id',
                'product_options.product_id',
            ])
            ->get();

        if (
            $values->count()
            !== $ids->count()
            || $values->contains(
                fn ($value): bool => (int) $value->product_id
                    !== $product->id
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'option_value_ids',
                    'Every option value must belong to the selected product.'
                );

            return;
        }

        $duplicateOption = $values
            ->groupBy(
                'product_option_id'
            )
            ->contains(
                fn ($group): bool => $group->count() > 1
            );

        if ($duplicateOption) {
            $validator
                ->errors()
                ->add(
                    'option_value_ids',
                    'A variant can contain only one value from each product option.'
                );
        }
    }
}
