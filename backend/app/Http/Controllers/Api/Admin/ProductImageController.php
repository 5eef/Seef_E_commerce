<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Http\Requests\Admin\UpdateProductImageRequest;
use App\Http\Resources\Admin\AdminProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Admin\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $productImageService
    ) {}

    public function index(
        Product $product
    ): AnonymousResourceCollection {
        Gate::authorize(
            'view',
            $product
        );

        $images = $product
            ->images()
            ->orderByDesc(
                'is_primary'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'id'
            )
            ->get();

        return AdminProductImageResource::collection(
            $images
        );
    }

    public function store(
        StoreProductImageRequest $request,
        Product $product
    ): JsonResponse {
        $image = $this
            ->productImageService
            ->create(
                $product,
                $request->validated()
            );

        return (
            new AdminProductImageResource(
                $image
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Product $product,
        ProductImage $image
    ): AdminProductImageResource {
        Gate::authorize(
            'view',
            $product
        );

        return new AdminProductImageResource(
            $image
        );
    }

    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $image
    ): AdminProductImageResource {
        $image = $this
            ->productImageService
            ->update(
                $product,
                $image,
                $request->validated()
            );

        return new AdminProductImageResource(
            $image
        );
    }

    public function destroy(
        Product $product,
        ProductImage $image
    ): Response {
        Gate::authorize(
            'update',
            $product
        );

        $this
            ->productImageService
            ->delete(
                $product,
                $image
            );

        return response()->noContent();
    }
}
