<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddCartItemRequest;
use App\Http\Requests\Store\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\User;
use App\Services\Store\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function show(
        Request $request
    ): JsonResponse {
        $context = $this
            ->cartService
            ->resolve(
                $request
            );

        return $this->cartResponse(
            $context,
            $this
                ->cartService
                ->hydrate(
                    $context['cart']
                )
        );
    }

    public function storeItem(
        AddCartItemRequest $request
    ): JsonResponse {
        $context = $this
            ->cartService
            ->resolve(
                $request
            );

        $validated = $request->validated();

        $cart = $this
            ->cartService
            ->addItem(
                $context['cart'],
                (int) $validated[
                    'product_variant_id'
                ],
                (int) $validated[
                    'quantity'
                ]
            );

        return $this->cartResponse(
            $context,
            $cart,
            201
        );
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        int $item
    ): JsonResponse {
        $context = $this
            ->cartService
            ->resolve(
                $request
            );

        $cart = $this
            ->cartService
            ->updateItem(
                $context['cart'],
                $item,
                (int) $request->validated(
                    'quantity'
                )
            );

        return $this->cartResponse(
            $context,
            $cart
        );
    }

    public function destroyItem(
        Request $request,
        int $item
    ): JsonResponse {
        $this->ensureActiveUser(
            $request
        );

        $context = $this
            ->cartService
            ->resolve(
                $request
            );

        $cart = $this
            ->cartService
            ->removeItem(
                $context['cart'],
                $item
            );

        return $this->cartResponse(
            $context,
            $cart
        );
    }

    public function clear(
        Request $request
    ): JsonResponse {
        $this->ensureActiveUser(
            $request
        );

        $context = $this
            ->cartService
            ->resolve(
                $request
            );

        $cart = $this
            ->cartService
            ->clear(
                $context['cart']
            );

        return $this->cartResponse(
            $context,
            $cart
        );
    }

    public function merge(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $cart = $this
            ->cartService
            ->mergeGuestCart(
                $request,
                $user
            );

        return response()
            ->json([
                'data' => new CartResource(
                    $cart
                ),
            ])
            ->withCookie(
                $this
                    ->cartService
                    ->forgetGuestCookie()
            );
    }

    /**
     * @param array{
     *     cart: Cart,
     *     guest_token: string|null,
     *     set_guest_cookie: bool
     * } $context
     */
    private function cartResponse(
        array $context,
        Cart $cart,
        int $status = 200
    ): JsonResponse {
        $response = response()->json([
            'data' => new CartResource(
                $cart
            ),
        ], $status);

        if (
            $context['set_guest_cookie']
            && $context['guest_token'] !== null
        ) {
            $response->withCookie(
                $this
                    ->cartService
                    ->makeGuestCookie(
                        $context['guest_token']
                    )
            );
        }

        return $response;
    }

    private function ensureActiveUser(
        Request $request
    ): void {
        if (
            $request->user() !== null
            && ! $request->user()->isActive()
        ) {
            abort(403);
        }
    }
}
