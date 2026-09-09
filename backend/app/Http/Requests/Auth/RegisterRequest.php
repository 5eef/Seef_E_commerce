<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Override;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),
            'email' => Str::lower(
                trim((string) $this->input('email'))
            ),
            'phone' => $this->filled('phone')
               ? trim((string) $this->input('phone'))
               : null,
        ]);

    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
                'unique:users,phone',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ];
    }
}
