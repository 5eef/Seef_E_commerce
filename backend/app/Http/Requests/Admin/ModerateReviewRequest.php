<?php

namespace App\Http\Requests\Admin;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && ($this->user()?->can('update', $review) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'rating' => ['prohibited'],
            'body' => ['prohibited'],
            'user_id' => ['prohibited'],
            'product_id' => ['prohibited'],
        ];
    }
}
