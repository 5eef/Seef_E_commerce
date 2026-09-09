<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProductOptionService
{
    public function createOption(
        Product $product,
        array $data
    ): ProductOption {
        return DB::transaction(
            function () use (
                $product,
                $data
            ): ProductOption {
                $option = $product
                    ->options()
                    ->create($data);

                return $this->loadOption(
                    $option
                );
            }
        );
    }

    public function updateOption(
        ProductOption $option,
        array $data
    ): ProductOption {
        return DB::transaction(
            function () use (
                $option,
                $data
            ): ProductOption {
                $option->update($data);

                $option->refresh();

                return $this->loadOption(
                    $option
                );
            }
        );
    }

    public function deleteOption(
        ProductOption $option
    ): void {
        DB::transaction(
            function () use ($option): void {
                $hasUsedValues = $option
                    ->values()
                    ->whereHas('variants')
                    ->exists();

                if ($hasUsedValues) {
                    throw new ConflictHttpException(
                        'This product option cannot be deleted while one or more values are used by product variants.'
                    );
                }

                /*
                 * Les valeurs non utilisées sont supprimées
                 * automatiquement par la FK cascadeOnDelete.
                 */
                $option->delete();
            }
        );
    }

    public function createValue(
        ProductOption $option,
        array $data
    ): ProductOptionValue {
        return DB::transaction(
            function () use (
                $option,
                $data
            ): ProductOptionValue {
                return $option
                    ->values()
                    ->create($data);
            }
        );
    }

    public function updateValue(
        ProductOptionValue $value,
        array $data
    ): ProductOptionValue {
        return DB::transaction(
            function () use (
                $value,
                $data
            ): ProductOptionValue {
                $value->update($data);

                $value->refresh();

                return $value;
            }
        );
    }

    public function deleteValue(
        ProductOptionValue $value
    ): void {
        DB::transaction(
            function () use ($value): void {
                if (
                    $value
                        ->variants()
                        ->exists()
                ) {
                    throw new ConflictHttpException(
                        'This option value cannot be deleted while it is used by product variants.'
                    );
                }

                $value->delete();
            }
        );
    }

    public function loadOption(
        ProductOption $option
    ): ProductOption {
        return $option->load([
            'values' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);
    }
}
