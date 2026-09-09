<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryIndexRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use App\Services\Admin\CategoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(
        CategoryIndexRequest $request
    ): AnonymousResourceCollection {
        $filters = $request->validated();

        $query = Category::query()
            ->with('parent')
            ->withCount([
                'children',
                'products',
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
                        );
                }
            );
        }

        if (
            array_key_exists(
                'is_active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                (bool) $filters['is_active']
            );
        }

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 25
                )
            )
            ->withQueryString();

        return AdminCategoryResource::collection(
            $categories
        );
    }

    public function store(
        StoreCategoryRequest $request
    ): JsonResponse {
        $category = $this
            ->categoryService
            ->create(
                $request->validated()
            );

        return (
            new AdminCategoryResource(
                $category
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Category $category
    ): AdminCategoryResource {
        Gate::authorize(
            'view',
            $category
        );

        return new AdminCategoryResource(
            $this
                ->categoryService
                ->loadForAdmin(
                    $category
                )
        );
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): AdminCategoryResource {
        $category = $this
            ->categoryService
            ->update(
                $category,
                $request->validated()
            );

        return new AdminCategoryResource(
            $category
        );
    }

    public function destroy(
        Category $category
    ): Response {
        Gate::authorize(
            'delete',
            $category
        );

        $this
            ->categoryService
            ->delete(
                $category
            );

        return response()->noContent();
    }
}
