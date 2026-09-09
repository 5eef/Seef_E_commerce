<?php

namespace App\Services\Admin;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CategoryService
{
    public function create(
        array $data
    ): Category {
        return DB::transaction(
            function () use ($data): Category {
                $category = Category::query()
                    ->create($data);

                return $this->loadForAdmin(
                    $category
                );
            }
        );
    }

    public function update(
        Category $category,
        array $data
    ): Category {
        return DB::transaction(
            function () use (
                $category,
                $data
            ): Category {
                if (
                    array_key_exists(
                        'parent_id',
                        $data
                    )
                ) {
                    $this->ensureValidParent(
                        $category,
                        $data['parent_id']
                    );
                }

                $category->update($data);

                $category->refresh();

                return $this->loadForAdmin(
                    $category
                );
            }
        );
    }

    public function delete(
        Category $category
    ): void {
        DB::transaction(
            function () use ($category): void {
                if (
                    $category
                        ->children()
                        ->exists()
                ) {
                    throw new ConflictHttpException(
                        'This category cannot be deleted while it has child categories.'
                    );
                }

                if (
                    $category
                        ->products()
                        ->exists()
                ) {
                    throw new ConflictHttpException(
                        'This category cannot be deleted while products are assigned to it.'
                    );
                }

                $category->delete();
            }
        );
    }

    public function loadForAdmin(
        Category $category
    ): Category {
        return $category
            ->load('parent')
            ->loadCount([
                'children',
                'products',
            ]);
    }

    private function ensureValidParent(
        Category $category,
        ?int $parentId
    ): void {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'A category cannot be its own parent.',
                ],
            ]);
        }

        $parent = Category::query()
            ->find($parentId);

        /*
         * Le FormRequest contient déjà exists,
         * mais cette vérification rend aussi
         * le service sûr s'il est appelé ailleurs.
         */
        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'The selected parent category does not exist.',
                ],
            ]);
        }

        /*
         * Protection contre les cycles profonds :
         *
         * A
         * └── B
         *     └── C
         *
         * Interdit ensuite :
         *
         * A.parent_id = C
         */
        $current = $parent;

        while ($current !== null) {
            if ($current->id === $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'The selected parent would create a category hierarchy cycle.',
                    ],
                ]);
            }

            if ($current->parent_id === null) {
                break;
            }

            $current = Category::query()
                ->find(
                    $current->parent_id
                );
        }
    }
}
