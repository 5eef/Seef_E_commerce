<?php

namespace App\Http\Requests\Store;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Review::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'body' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'order_item_id' => [
                'nullable',
                'integer',
                'exists:order_items,id',
            ],

            /*
             * Ces champs sont toujours calculés
             * côté serveur.
             */
            'user_id' => [
                'prohibited',
            ],

            'product_id' => [
                'prohibited',
            ],

            'status' => [
                'prohibited',
            ],

            'is_verified_purchase' => [
                'prohibited',
            ],

            'approved_at' => [
                'prohibited',
            ],
        ];
    }
}
