<?php

namespace App\Http\Requests\Admin;

use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inventory = $this->route('inventory');

        return $inventory instanceof Inventory && ($this->user()?->can('update', $inventory) ?? false);
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::in(['increase', 'decrease', 'set'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'reserved_quantity' => ['prohibited'],
            'on_hand_quantity' => ['prohibited'],
        ];
    }
}
