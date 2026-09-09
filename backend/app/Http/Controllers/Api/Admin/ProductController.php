<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Product;
use App\Services\Admin\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(
        ProductIndexRequest $request
    ): AnonymousResourceCollection {
        $filters = $request->validated();

        $query = Product::query();

        match (
            $filters['trashed'] ?? 'without'
        ) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        $query
            ->with([
                'categories' => fn ($relation) => $relation
                    ->orderBy(
                        'categories.sort_order'
                    )
                    ->orderBy(
                        'categories.name'
                    ),
            ])
            ->withCount([
                'variants',
                'images',
                'reviews',
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
                            'slug',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'sku',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (
            array_key_exists(
                'featured',
                $filters
            )
        ) {
            $query->where(
                'is_featured',
                (bool) $filters['featured']
            );
        }

        if (
            ! empty(
                $filters['category_id']
            )
        ) {
            $categoryId = (int) $filters[
                'category_id'
            ];

            $query->whereHas(
                'categories',
                fn ($query) => $query
                    ->where(
                        'categories.id',
                        $categoryId
                    )
            );
        }

        match (
            $filters['sort'] ?? 'newest'
        ) {
            'oldest' => $query
                ->orderBy('created_at')
                ->orderBy('id'),

            'name_asc' => $query
                ->orderBy('name')
                ->orderBy('id'),

            'name_desc' => $query
                ->orderByDesc('name')
                ->orderByDesc('id'),

            'price_asc' => $query
                ->orderBy('base_price')
                ->orderBy('id'),

            'price_desc' => $query
                ->orderByDesc(
                    'base_price'
                )
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc('id'),
        };

        $products = $query
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 25
                )
            )
            ->withQueryString();

        return AdminProductResource::collection(
            $products
        );
    }

    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $product = $this
            ->productService
            ->create(
                $request->validated()
            );

        return (
            new AdminProductResource(
                $product
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Product $product
    ): AdminProductResource {
        Gate::authorize(
            'view',
            $product
        );

        return new AdminProductResource(
            $this
                ->productService
                ->loadForAdmin(
                    $product
                )
        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): AdminProductResource {
        $product = $this
            ->productService
            ->update(
                $product,
                $request->validated()
            );

        return new AdminProductResource(
            $product
        );
    }

    public function destroy(
        Product $product
    ): Response {
        Gate::authorize(
            'delete',
            $product
        );

        $this
            ->productService
            ->delete(
                $product
            );

        return response()->noContent();
    }

    public function restore(
        int $product
    ): AdminProductResource {
        /** @var Product $trashedProduct */
        $trashedProduct = Product::query()
            ->onlyTrashed()
            ->findOrFail(
                $product
            );

        Gate::authorize(
            'restore',
            $trashedProduct
        );

        return new AdminProductResource(
            $this
                ->productService
                ->restore(
                    $product
                )
        );
    }
}
