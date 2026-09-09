<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Category::class
        ) ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $name = trim(
            (string) $this->input('name')
        );

        $slug = $this->filled('slug')
            ? Str::slug(
                (string) $this->input('slug')
            )
            : Str::slug($name);

        $this->merge([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                'unique:categories,slug',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'image_path' => [
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
