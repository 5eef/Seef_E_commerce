<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')

            ->with([
                'children' => fn (
                    $relation
                ) => $relation
                    ->active()

                    ->withCount([
                        'products' => fn (
                            $query
                        ) => $query
                            ->published(),
                    ])

                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy('name'),
            ])

            ->withCount([
                'products' => fn (
                    $query
                ) => $query
                    ->published(),
            ])

            ->orderBy(
                'sort_order'
            )
            ->orderBy('name')
            ->get();

        return CategoryResource::collection(
            $categories
        );
    }

    public function show(
        Category $category
    ): CategoryResource {
        abort_unless(
            $category->is_active,
            404
        );

        $category->load([
            'parent' => fn (
                $relation
            ) => $relation
                ->active(),

            'children' => fn (
                $relation
            ) => $relation
                ->active()

                ->withCount([
                    'products' => fn (
                        $query
                    ) => $query
                        ->published(),
                ])

                ->orderBy(
                    'sort_order'
                )
                ->orderBy('name'),
        ]);

        $category->loadCount([
            'products' => fn (
                $query
            ) => $query
                ->published(),
        ]);

        return new CategoryResource(
            $category
        );
    }
}
