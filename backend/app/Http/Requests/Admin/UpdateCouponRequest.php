<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon && ($this->user()?->can('update', $coupon) ?? false);
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
        $coupon = $this->route('coupon');
        $type = $this->input('type', $coupon instanceof Coupon ? $coupon->type : null);

        return [
            'code' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($coupon)],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'type' => ['sometimes', 'required', Rule::in(['fixed', 'percentage'])],
            'value' => ['sometimes', 'required', 'numeric', 'gt:0', Rule::when($type === 'percentage', ['lte:100'])],
            'minimum_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximum_discount_amount' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
