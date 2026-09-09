<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantIndexRequest;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Http\Resources\Admin\AdminProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\ProductVariantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantService $productVariantService
    ) {}

    public function index(
        ProductVariantIndexRequest $request,
        Product $product
    ): AnonymousResourceCollection {
        $filters = $request->validated();

        $query = $product
            ->variants();

        match (
            $filters['trashed'] ?? 'without'
        ) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        $query
            ->with([
                'optionValues' => fn ($relation) => $relation
                    ->orderBy(
                        'product_option_values.product_option_id'
                    )
                    ->orderBy(
                        'product_option_values.sort_order'
                    )
                    ->orderBy(
                        'product_option_values.id'
                    ),

                'inventory',
            ])
            ->withCount([
                'cartItems',
                'orderItems',
            ]);

        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(
                function (
                    Builder $query
                ) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'sku',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'barcode',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        if (
            array_key_exists(
                'active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                (bool) $filters['active']
            );
        }

        match (
            $filters['sort'] ?? 'sort_order'
        ) {
            'newest' => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),

            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name_asc' => $query
                ->orderBy('name')
                ->orderBy('id'),

            'name_desc' => $query
                ->orderByDesc('name')
                ->orderByDesc('id'),

            'sku_asc' => $query
                ->orderBy('sku')
                ->orderBy('id'),

            'sku_desc' => $query
                ->orderByDesc('sku')
                ->orderByDesc('id'),

            'price_asc' => $query
                ->orderByRaw(
                    'COALESCE(price, ?) ASC',
                    [$product->base_price]
                )
                ->orderBy('id'),

            'price_desc' => $query
                ->orderByRaw(
                    'COALESCE(price, ?) DESC',
                    [$product->base_price]
                )
                ->orderByDesc('id'),

            default => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
        };

        $variants = $query
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 25
                )
            )
            ->withQueryString();

        return AdminProductVariantResource::collection(
            $variants
        );
    }

    public function store(
        StoreProductVariantRequest $request,
        Product $product
    ): JsonResponse {
        $variant = $this
            ->productVariantService
            ->create(
                $product,
                $request->validated()
            );

        return (
            new AdminProductVariantResource(
                $variant
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Product $product,
        ProductVariant $variant
    ): AdminProductVariantResource {
        Gate::authorize(
            'view',
            $product
        );

        return new AdminProductVariantResource(
            $this
                ->productVariantService
                ->loadForAdmin(
                    $variant
                )
        );
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant
    ): AdminProductVariantResource {
        $variant = $this
            ->productVariantService
            ->update(
                $variant,
                $request->validated()
            );

        return new AdminProductVariantResource(
            $variant
        );
    }

    public function destroy(
        Product $product,
        ProductVariant $variant
    ): Response {
        Gate::authorize(
            'update',
            $product
        );

        $this
            ->productVariantService
            ->delete(
                $variant
            );

        return response()->noContent();
    }

    public function restore(
        Product $product,
        int $variant
    ): AdminProductVariantResource {
        Gate::authorize(
            'update',
            $product
        );

        return new AdminProductVariantResource(
            $this
                ->productVariantService
                ->restore(
                    $product,
                    $variant
                )
        );
    }
}
