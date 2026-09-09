<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CreateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request, Product $product): AnonymousResourceCollection
    {
        abort_unless($product->status === 'active' && $product->published_at?->isPast(), 404);

        return ReviewResource::collection(
            $product->reviews()->where('status', 'approved')->with('user')->latest()->paginate(10)
        );
    }

    public function store(CreateReviewRequest $request, Product $product): ReviewResource
    {
        abort_unless($product->status === 'active' && $product->published_at?->isPast(), 404);
        $validated = $request->validated();

        $orderItemQuery = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['delivered', 'completed']));

        $orderItem = isset($validated['order_item_id'])
            ? $orderItemQuery->whereKey($validated['order_item_id'])->first()
            : $orderItemQuery->latest('id')->first();

        if ($orderItem === null) {
            throw ValidationException::withMessages([
                'order_item_id' => ['A delivered purchase is required before reviewing this product.'],
            ]);
        }

        if (Review::query()->where('user_id', $request->user()->id)->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages([
                'product' => ['You have already reviewed this product.'],
            ]);
        }

        $review = Review::query()->create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'order_item_id' => $orderItem->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        return new ReviewResource($review->load('user'));
    }
}
