<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductOptionRequest;
use App\Http\Requests\Admin\UpdateProductOptionRequest;
use App\Http\Resources\Admin\AdminProductOptionResource;
use App\Models\Product;
use App\Models\ProductOption;
use App\Services\Admin\ProductOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductOptionController extends Controller
{
    public function __construct(
        private readonly ProductOptionService $productOptionService
    ) {}

    public function index(
        Product $product
    ): AnonymousResourceCollection {
        Gate::authorize(
            'view',
            $product
        );

        $options = $product
            ->options()
            ->with([
                'values' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return AdminProductOptionResource::collection(
            $options
        );
    }

    public function store(
        StoreProductOptionRequest $request,
        Product $product
    ): JsonResponse {
        $option = $this
            ->productOptionService
            ->createOption(
                $product,
                $request->validated()
            );

        return (
            new AdminProductOptionResource(
                $option
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Product $product,
        ProductOption $option
    ): AdminProductOptionResource {
        Gate::authorize(
            'view',
            $product
        );

        return new AdminProductOptionResource(
            $this
                ->productOptionService
                ->loadOption(
                    $option
                )
        );
    }

    public function update(
        UpdateProductOptionRequest $request,
        Product $product,
        ProductOption $option
    ): AdminProductOptionResource {
        $option = $this
            ->productOptionService
            ->updateOption(
                $option,
                $request->validated()
            );

        return new AdminProductOptionResource(
            $option
        );
    }

    public function destroy(
        Product $product,
        ProductOption $option
    ): Response {
        Gate::authorize(
            'update',
            $product
        );

        $this
            ->productOptionService
            ->deleteOption(
                $option
            );

        return response()->noContent();
    }
}
