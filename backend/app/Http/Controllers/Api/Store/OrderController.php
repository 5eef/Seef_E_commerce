<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Order::class);

        return OrderResource::collection(
            request()->user()->orders()
                ->withCount('items')
                ->latest('created_at')
                ->latest('id')
                ->paginate(10)
        );
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load([
            'items',
            'payments',
            'shipments',
            'returns.items',
            'statusHistories' => fn ($query) => $query->oldest('created_at')->oldest('id'),
        ]));
    }
}
