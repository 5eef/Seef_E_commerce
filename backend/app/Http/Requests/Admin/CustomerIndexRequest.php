<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'viewAny',
            User::class
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

        if ($this->has('status')) {
            $data['status'] = Str::lower(
                trim(
                    (string) $this->input('status')
                )
            );
        }

        if ($this->has('sort')) {
            $data['sort'] = Str::lower(
                trim(
                    (string) $this->input('sort')
                )
            );
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
                'max:150',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'suspended',
                    'disabled',
                ]),
            ],

            'sort' => [
                'sometimes',
                Rule::in([
                    'newest',
                    'oldest',
                    'name_asc',
                    'name_desc',
                    'email_asc',
                    'email_desc',
                ]),
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
