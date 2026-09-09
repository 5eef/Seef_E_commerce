<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Review::class);
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        return ReviewResource::collection(
            Review::query()->with(['user', 'product'])
                ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest('id')
                ->paginate((int) ($validated['per_page'] ?? 25))
        );
    }

    public function update(ModerateReviewRequest $request, Review $review): ReviewResource
    {
        $status = $request->validated('status');
        $review->update([
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        return new ReviewResource($review->refresh()->load('user'));
    }
}
