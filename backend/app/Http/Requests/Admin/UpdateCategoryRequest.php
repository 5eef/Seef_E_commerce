<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            && (
                $this->user()?->can(
                    'update',
                    $category
                ) ?? false
            );
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim(
                (string) $this->input('name')
            );
        }

        if ($this->has('slug')) {
            $data['slug'] = Str::slug(
                (string) $this->input('slug')
            );
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $category = $this->route('category');

        $slugRule = Rule::unique(
            'categories',
            'slug'
        );

        $parentRules = [
            'sometimes',
            'nullable',
            'integer',
            'exists:categories,id',
        ];

        if ($category instanceof Category) {
            $slugRule->ignore($category);

            $parentRules[] = Rule::notIn([
                $category->getKey(),
            ]);
        }

        return [
            'parent_id' => $parentRules,

            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                $slugRule,
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],

            'image_path' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'id' => [
                'prohibited',
            ],

            'created_at' => [
                'prohibited',
            ],

            'updated_at' => [
                'prohibited',
            ],
        ];
    }
}
