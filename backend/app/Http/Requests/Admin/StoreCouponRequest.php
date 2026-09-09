<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Coupon::class) ?? false;
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => Str::upper(trim((string) $this->input('code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:coupons,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => ['required', 'numeric', 'gt:0', Rule::when($this->input('type') === 'percentage', ['lte:100'])],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
