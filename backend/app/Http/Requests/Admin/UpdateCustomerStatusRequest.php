<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class UpdateCustomerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * Le client cible sera recherché ensuite
         * exclusivement parmi role=customer.
         *
         * Ici nous validons simplement que
         * l'acteur est autorisé à administrer
         * les utilisateurs.
         */
        return $this->user()?->can(
            'viewAny',
            User::class
        ) ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => Str::lower(
                    trim(
                        (string) $this->input('status')
                    )
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'disabled',
                ]),
            ],

            /*
             * Champs sensibles explicitement
             * interdits sur cet endpoint.
             */
            'role' => [
                'prohibited',
            ],

            'password' => [
                'prohibited',
            ],

            'email' => [
                'prohibited',
            ],

            'phone' => [
                'prohibited',
            ],

            'name' => [
                'prohibited',
            ],

            'email_verified_at' => [
                'prohibited',
            ],

            'last_login_at' => [
                'prohibited',
            ],

            'remember_token' => [
                'prohibited',
            ],
        ];
    }
}
