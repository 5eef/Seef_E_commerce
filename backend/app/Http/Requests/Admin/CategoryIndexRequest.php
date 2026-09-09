<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class CategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'viewAny',
            Category::class
        ) ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('q')) {
            $data['q'] = trim(
                (string) $this->input('q')
            );
        }

        if ($this->has('is_active')) {
            $value = $this->input(
                'is_active'
            );

            if (is_string($value)) {
                $normalized = Str::lower(
                    trim($value)
                );

                if ($normalized === 'true') {
                    $value = true;
                }

                if ($normalized === 'false') {
                    $value = false;
                }
            }

            $data['is_active'] = $value;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'q' => [
                'sometimes',
                'nullable',
                'string',
                'min:2',
                'max:120',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'between:1,100',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }
}
