<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(
                trim((string) $this->input('email'))
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ];
    }
}
