<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ProductIndexRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    public function index(
        ProductIndexRequest $request
    ): ProductCollection {
        $filters = $request->validated();
        $query = $this->baseProductQuery();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return new ProductCollection(
            $query
                ->paginate((int) ($filters['per_page'] ?? 12))
                ->withQueryString()
        );
    }

    private function baseProductQuery(): Builder
    {
        return Product::query()
            ->published()
            ->with([
                'categories' => fn ($relation) => $relation
                    ->where(
                        'categories.is_active',
                        true
                    )
                    ->orderBy(
                        'categories.sort_order'
                    )
                    ->orderBy(
                        'categories.name'
                    ),

                'images' => fn ($relation) => $relation
                    ->orderByDesc(
                        'is_primary'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy('id'),

                'variants' => fn ($relation) => $relation
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy('id')
                    ->with(
                        'inventory'
                    ),
            ])
            ->withCount([
                'reviews' => fn ($query) => $query
                    ->where(
                        'status',
                        'approved'
                    ),
            ])
            ->withAvg([
                'reviews' => fn ($query) => $query
                    ->where(
                        'status',
                        'approved'
                    ),
            ], 'rating');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $this->applySearchFilter($query, $filters);
        $this->applyCategoryFilter($query, $filters);
        $this->applyPriceFilters($query, $filters);
        $this->applyAvailabilityFilters($query, $filters);
    }

    private function applySearchFilter(Builder $query, array $filters): void
    {
        if (empty($filters['q'])) {
            return;
        }

        $search = $filters['q'];
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('sku', 'like', "%{$search}%")
            ->orWhere('short_description', 'like', "%{$search}%"));
    }

    private function applyCategoryFilter(Builder $query, array $filters): void
    {
        if (empty($filters['category'])) {
            return;
        }

        $query->whereHas('categories', fn ($query) => $query
            ->where('categories.slug', $filters['category'])
            ->where('categories.is_active', true));
    }

    private function applyPriceFilters(Builder $query, array $filters): void
    {
        foreach (['min_price' => '>=', 'max_price' => '<='] as $key => $operator) {
            if (! array_key_exists($key, $filters) || $filters[$key] === null) {
                continue;
            }

            $price = $filters[$key];
            $query->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->whereNotNull('sale_price')
                    ->where('sale_price', $operator, $price))
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('sale_price')
                    ->where('base_price', $operator, $price)));
        }
    }

    private function applyAvailabilityFilters(Builder $query, array $filters): void
    {
        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        if (! array_key_exists('in_stock', $filters)) {
            return;
        }

        $stockConstraint = fn ($query) => $query
            ->where('is_active', true)
            ->whereHas('inventory', fn ($query) => $query
                ->whereColumn('on_hand_quantity', '>', 'reserved_quantity'));

        if ((bool) $filters['in_stock']) {
            $query->whereHas('variants', $stockConstraint);
        } else {
            $query->whereDoesntHave('variants', $stockConstraint);
        }
    }

    private function applySorting(Builder $query, array $filters): void
    {
        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, base_price) ASC')->orderBy('id'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, base_price) DESC')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    public function show(
        Product $product
    ): ProductResource {
        abort_unless(
            $product->status === 'active'
            && $product->published_at !== null
            && ! $product
                ->published_at
                ->isFuture(),
            404
        );

        $product->load([
            'categories' => fn ($relation) => $relation
                ->where(
                    'categories.is_active',
                    true
                )
                ->orderBy(
                    'categories.sort_order'
                )
                ->orderBy(
                    'categories.name'
                ),

            'images' => fn ($relation) => $relation
                ->orderByDesc(
                    'is_primary'
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy('id'),

            'variants' => fn ($relation) => $relation
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy('id')
                ->with([
                    'inventory',

                    'optionValues' => fn (
                        $relation
                    ) => $relation
                        ->orderBy(
                            'product_option_values.sort_order'
                        )
                        ->orderBy(
                            'product_option_values.id'
                        ),
                ]),

            'options' => fn ($relation) => $relation
                ->orderBy(
                    'sort_order'
                )
                ->orderBy('id')
                ->with([
                    'values' => fn (
                        $relation
                    ) => $relation
                        ->orderBy(
                            'sort_order'
                        )
                        ->orderBy('id'),
                ]),

            'reviews' => fn ($relation) => $relation
                ->where(
                    'status',
                    'approved'
                )
                ->latest(
                    'created_at'
                )
                ->with('user'),
        ]);

        $product->loadCount([
            'reviews' => fn ($query) => $query
                ->where(
                    'status',
                    'approved'
                ),
        ]);

        $product->loadAvg([
            'reviews' => fn ($query) => $query
                ->where(
                    'status',
                    'approved'
                ),
        ], 'rating');

        return new ProductResource(
            $product
        );
    }
}
