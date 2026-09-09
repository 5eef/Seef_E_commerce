<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\Admin\AdminCouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CouponController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Coupon::class);
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $coupons = Coupon::query()
            ->withCount('usages')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested
                ->where('code', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('name', 'like', '%'.$request->string('q')->toString().'%')))
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return AdminCouponResource::collection($coupons);
    }

    public function store(StoreCouponRequest $request): AdminCouponResource
    {
        return new AdminCouponResource(Coupon::query()->create($request->validated())->loadCount('usages'));
    }

    public function show(Coupon $coupon): AdminCouponResource
    {
        Gate::authorize('view', $coupon);

        return new AdminCouponResource($coupon->loadCount('usages'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): AdminCouponResource
    {
        $coupon->update($request->validated());

        return new AdminCouponResource($coupon->refresh()->loadCount('usages'));
    }

    public function destroy(Coupon $coupon): Response
    {
        Gate::authorize('delete', $coupon);
        $coupon->update(['is_active' => false]);

        return response()->noContent();
    }
}
