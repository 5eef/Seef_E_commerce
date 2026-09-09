<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Store\CartService;
use App\Services\Store\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $context = $this->cartService->resolve($request);
        $order = $this->checkoutService->checkout($context['cart'], $request->validated());
        $response = response()->json(['data' => new OrderResource($order)], 201);

        if ($context['guest_token'] !== null) {
            $response->withCookie($this->cartService->forgetGuestCookie());
        }

        return $response;
    }
}
