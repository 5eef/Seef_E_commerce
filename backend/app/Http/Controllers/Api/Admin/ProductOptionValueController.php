<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductOptionValueRequest;
use App\Http\Requests\Admin\UpdateProductOptionValueRequest;
use App\Http\Resources\Admin\AdminProductOptionValueResource;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Services\Admin\ProductOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductOptionValueController extends Controller
{
    public function __construct(
        private readonly ProductOptionService $productOptionService
    ) {}

    public function store(
        StoreProductOptionValueRequest $request,
        Product $product,
        ProductOption $option
    ): JsonResponse {
        $value = $this
            ->productOptionService
            ->createValue(
                $option,
                $request->validated()
            );

        return (
            new AdminProductOptionValueResource(
                $value
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProductOptionValueRequest $request,
        Product $product,
        ProductOption $option,
        ProductOptionValue $value
    ): AdminProductOptionValueResource {
        $value = $this
            ->productOptionService
            ->updateValue(
                $value,
                $request->validated()
            );

        return new AdminProductOptionValueResource(
            $value
        );
    }

    public function destroy(
        Product $product,
        ProductOption $option,
        ProductOptionValue $value
    ): Response {
        Gate::authorize(
            'update',
            $product
        );

        $this
            ->productOptionService
            ->deleteValue(
                $value
            );

        return response()->noContent();
    }
}
