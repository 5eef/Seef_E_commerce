<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $recentOrders = Order::query()->withCount('items')->latest()->limit(5)->get();
        $trend = Order::query()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count, SUM(grand_total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json(['data' => [
            'revenue' => Order::query()->where('payment_status', 'paid')->sum('grand_total'),
            'orders' => Order::query()->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'products' => Product::query()->count(),
            'low_stock' => Inventory::query()->whereColumn('on_hand_quantity', '<=', 'low_stock_threshold')->count(),
            'recent_orders' => AdminOrderResource::collection($recentOrders),
            'trend' => $trend,
        ]]);
    }
}
