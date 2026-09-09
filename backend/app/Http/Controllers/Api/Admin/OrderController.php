<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use App\Services\Admin\OrderService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(
        OrderIndexRequest $request
    ): AnonymousResourceCollection {
        $orders = $this
            ->orderService
            ->paginate(
                $request->validated()
            );

        return AdminOrderResource::collection(
            $orders
        );
    }

    public function show(
        Order $order
    ): AdminOrderResource {
        Gate::authorize(
            'view',
            $order
        );

        return new AdminOrderResource(
            $this
                ->orderService
                ->loadForAdmin(
                    $order
                )
        );
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    ): AdminOrderResource {
        Gate::authorize(
            'update',
            $order
        );

        $order = $this
            ->orderService
            ->updateStatus(
                $order,
                $request->user(),
                $request->validated('status'),
                $request->validated('note')
            );

        return new AdminOrderResource(
            $order
        );
    }
}
