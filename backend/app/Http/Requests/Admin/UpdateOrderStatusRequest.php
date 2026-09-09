<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Override;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && (
                $this->user()?->can(
                    'update',
                    $order
                ) ?? false
            );
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('status')) {
            $data['status'] = Str::lower(
                trim(
                    (string) $this->input('status')
                )
            );
        }

        if ($this->has('note')) {
            $note = trim(
                (string) $this->input('note')
            );

            $data['note'] = $note !== ''
                ? $note
                : null;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                    'completed',
                ]),
            ],

            'note' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'payment_status' => ['prohibited'],

            'subtotal' => ['prohibited'],
            'discount_total' => ['prohibited'],
            'shipping_total' => ['prohibited'],
            'tax_total' => ['prohibited'],
            'grand_total' => ['prohibited'],
            'currency' => ['prohibited'],

            'user_id' => ['prohibited'],
            'coupon_id' => ['prohibited'],

            'customer_name' => ['prohibited'],
            'email' => ['prohibited'],
            'phone' => ['prohibited'],

            'shipping_address' => ['prohibited'],
            'billing_address' => ['prohibited'],

            'customer_note' => ['prohibited'],
            'admin_note' => ['prohibited'],
            'coupon_code' => ['prohibited'],

            'placed_at' => ['prohibited'],
            'confirmed_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'completed_at' => ['prohibited'],

            'order_number' => ['prohibited'],

            'items' => ['prohibited'],
            'payments' => ['prohibited'],
            'shipments' => ['prohibited'],
            'returns' => ['prohibited'],
        ];
    }
}
