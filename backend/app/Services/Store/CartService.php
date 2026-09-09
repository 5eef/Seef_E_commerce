<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

class CartService
{
    public const GUEST_COOKIE = 'cart_token';

    private const GUEST_CART_TTL_DAYS = 30;

    /**
     * @return array{
     *     cart: Cart,
     *     guest_token: string|null,
     *     set_guest_cookie: bool
     * }
     */
    public function resolve(
        Request $request
    ): array {
        $user = $request->user();

        if (
            $user !== null
            && ! $user->isActive()
        ) {
            abort(403);
        }

        if ($user !== null) {
            return [
                'cart' => $this->getOrCreateUserCart(
                    $user
                ),
                'guest_token' => null,
                'set_guest_cookie' => false,
            ];
        }

        $guestToken = $this->guestTokenFromRequest(
            $request
        );

        if ($guestToken !== null) {
            $cart = Cart::query()
                ->where(
                    'guest_token',
                    $guestToken
                )
                ->whereNull('user_id')
                ->first();

            if (
                $cart !== null
                && $cart->status === 'active'
                && (
                    $cart->expires_at === null
                    || $cart->expires_at->isFuture()
                )
            ) {
                return [
                    'cart' => $cart,
                    'guest_token' => $guestToken,
                    'set_guest_cookie' => false,
                ];
            }

            if (
                $cart !== null
                && $cart->status === 'active'
            ) {
                $cart->status = 'expired';
                $cart->save();
            }
        }

        $guestToken = (string) Str::uuid();

        $cart = Cart::query()->create([
            'guest_token' => $guestToken,
            'status' => 'active',
            'expires_at' => now()->addDays(
                self::GUEST_CART_TTL_DAYS
            ),
        ]);

        return [
            'cart' => $cart,
            'guest_token' => $guestToken,
            'set_guest_cookie' => true,
        ];
    }

    public function hydrate(
        Cart $cart
    ): Cart {
        $cart->load([
            'items' => fn ($query) => $query
                ->orderBy('id')
                ->with([
                    'variant' => fn ($query) => $query
                        ->withTrashed()
                        ->with([
                            'product' => fn ($query) => $query
                                ->withTrashed(),

                            'inventory',

                            'optionValues' => fn ($query) => $query
                                ->orderBy(
                                    'product_option_values.sort_order'
                                )
                                ->orderBy(
                                    'product_option_values.id'
                                ),
                        ]),
                ]),
        ]);

        foreach ($cart->items as $item) {
            $this->hydrateItem(
                $item
            );
        }

        return $cart;
    }

    public function addItem(
        Cart $cart,
        int $variantId,
        int $quantity
    ): Cart {
        DB::transaction(
            function () use (
                $cart,
                $variantId,
                $quantity
            ): void {
                $variant = $this
                    ->purchasableVariantOrFail(
                        $variantId,
                        true
                    );

                $availableQuantity = $this
                    ->availableQuantity(
                        $variant
                    );

                $item = CartItem::query()
                    ->where(
                        'cart_id',
                        $cart->id
                    )
                    ->where(
                        'product_variant_id',
                        $variant->id
                    )
                    ->lockForUpdate()
                    ->first();

                $currentQuantity = $item?->quantity ?? 0;

                $desiredQuantity = $currentQuantity
                    + $quantity;

                $this->validateQuantity(
                    $desiredQuantity,
                    $availableQuantity
                );

                if ($item === null) {
                    $cart
                        ->items()
                        ->create([
                            'product_variant_id' => $variant->id,
                            'quantity' => $desiredQuantity,
                        ]);
                } else {
                    $item->quantity = $desiredQuantity;
                    $item->save();
                }

                $this->touchGuestCartExpiry(
                    $cart
                );
            }
        );

        return $this->hydrate(
            $cart->fresh()
        );
    }

    public function updateItem(
        Cart $cart,
        int $itemId,
        int $quantity
    ): Cart {
        DB::transaction(
            function () use (
                $cart,
                $itemId,
                $quantity
            ): void {
                $item = CartItem::query()
                    ->where(
                        'cart_id',
                        $cart->id
                    )
                    ->whereKey(
                        $itemId
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $variant = $this
                    ->purchasableVariantOrFail(
                        $item->product_variant_id,
                        true
                    );

                $this->validateQuantity(
                    $quantity,
                    $this->availableQuantity(
                        $variant
                    )
                );

                $item->quantity = $quantity;
                $item->save();

                $this->touchGuestCartExpiry(
                    $cart
                );
            }
        );

        return $this->hydrate(
            $cart->fresh()
        );
    }

    public function removeItem(
        Cart $cart,
        int $itemId
    ): Cart {
        DB::transaction(
            function () use (
                $cart,
                $itemId
            ): void {
                $item = CartItem::query()
                    ->where(
                        'cart_id',
                        $cart->id
                    )
                    ->whereKey(
                        $itemId
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $item->delete();

                $this->touchGuestCartExpiry(
                    $cart
                );
            }
        );

        return $this->hydrate(
            $cart->fresh()
        );
    }

    public function clear(
        Cart $cart
    ): Cart {
        DB::transaction(
            function () use ($cart): void {
                $cart
                    ->items()
                    ->delete();

                $this->touchGuestCartExpiry(
                    $cart
                );
            }
        );

        return $this->hydrate(
            $cart->fresh()
        );
    }

    /**
     * Fusionne le panier invité porté par le cookie
     * dans le panier de l'utilisateur connecté.
     *
     * Le stock n'est PAS réservé ici.
     */
    public function mergeGuestCart(
        Request $request,
        User $user
    ): Cart {
        $guestToken = $this->guestTokenFromRequest(
            $request
        );

        if ($guestToken === null) {
            return $this->hydrate(
                $this->getOrCreateUserCart(
                    $user
                )
            );
        }

        return DB::transaction(
            function () use (
                $guestToken,
                $user
            ): Cart {
                $guestCart = Cart::query()
                    ->where(
                        'guest_token',
                        $guestToken
                    )
                    ->whereNull('user_id')
                    ->where(
                        'status',
                        'active'
                    )
                    ->lockForUpdate()
                    ->first();

                if ($guestCart === null) {
                    return $this->hydrate(
                        $this->getOrCreateUserCart(
                            $user
                        )
                    );
                }

                $userCart = Cart::query()
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                /*
                 * Si aucun panier utilisateur n'existe,
                 * le panier invité devient directement
                 * son panier actif.
                 */
                if ($userCart === null) {
                    $guestCart->load([
                        'items.variant' => fn ($query) => $query
                            ->withTrashed()
                            ->with([
                                'product' => fn ($query) => $query
                                    ->withTrashed(),
                                'inventory',
                            ]),
                    ]);

                    foreach ($guestCart->items as $guestItem) {
                        if (! $this->isPurchasable($guestItem->variant)) {
                            $guestItem->delete();

                            continue;
                        }

                        $availableQuantity = $this->availableQuantity(
                            $guestItem->variant
                        );

                        if ($availableQuantity <= 0) {
                            $guestItem->delete();

                            continue;
                        }

                        $guestItem->quantity = min(
                            99,
                            $availableQuantity,
                            $guestItem->quantity
                        );
                        $guestItem->save();
                    }

                    if ($guestCart->items()->doesntExist()) {
                        $userCart = $user
                            ->carts()
                            ->create([
                                'status' => 'active',
                                'expires_at' => null,
                            ]);

                        $guestCart->status = 'converted';
                        $guestCart->guest_token = null;
                        $guestCart->expires_at = now();
                        $guestCart->save();

                        return $this->hydrate($userCart);
                    }

                    $guestCart->user_id = $user->id;
                    $guestCart->guest_token = null;
                    $guestCart->expires_at = null;
                    $guestCart->save();

                    return $this->hydrate(
                        $guestCart->fresh()
                    );
                }

                $guestCart->load([
                    'items.variant' => fn ($query) => $query
                        ->withTrashed()
                        ->with([
                            'product' => fn ($query) => $query
                                ->withTrashed(),

                            'inventory',
                        ]),
                ]);

                foreach ($guestCart->items as $guestItem) {
                    $variant = $guestItem->variant;

                    if (
                        ! $this->isPurchasable(
                            $variant
                        )
                    ) {
                        continue;
                    }

                    $availableQuantity = $this
                        ->availableQuantity(
                            $variant
                        );

                    if ($availableQuantity <= 0) {
                        continue;
                    }

                    $userItem = CartItem::query()
                        ->where(
                            'cart_id',
                            $userCart->id
                        )
                        ->where(
                            'product_variant_id',
                            $guestItem->product_variant_id
                        )
                        ->lockForUpdate()
                        ->first();

                    $desiredQuantity = (
                        $userItem?->quantity ?? 0
                    ) + $guestItem->quantity;

                    /*
                     * Pendant une fusion, on plafonne
                     * plutôt que de faire échouer le login.
                     */
                    $desiredQuantity = min(
                        99,
                        $availableQuantity,
                        $desiredQuantity
                    );

                    if ($desiredQuantity <= 0) {
                        continue;
                    }

                    if ($userItem === null) {
                        $userCart
                            ->items()
                            ->create([
                                'product_variant_id' => $guestItem
                                    ->product_variant_id,

                                'quantity' => $desiredQuantity,
                            ]);
                    } else {
                        $userItem->quantity = $desiredQuantity;
                        $userItem->save();
                    }
                }

                $guestCart
                    ->items()
                    ->delete();

                $guestCart->status = 'converted';
                $guestCart->guest_token = null;
                $guestCart->expires_at = now();
                $guestCart->save();

                return $this->hydrate(
                    $userCart->fresh()
                );
            }
        );
    }

    public function makeGuestCookie(
        string $guestToken
    ): Cookie {
        return cookie(
            self::GUEST_COOKIE,
            Crypt::encryptString(
                $guestToken
            ),
            self::GUEST_CART_TTL_DAYS
                * 24
                * 60,
            '/',
            null,
            (bool) config(
                'session.secure',
                false
            ),
            true,
            false,
            'lax'
        );
    }

    public function forgetGuestCookie(): Cookie
    {
        return cookie()->forget(
            self::GUEST_COOKIE
        );
    }

    private function getOrCreateUserCart(
        User $user
    ): Cart {
        $cart = Cart::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'status',
                'active'
            )
            ->latest('id')
            ->first();

        if ($cart !== null) {
            return $cart;
        }

        return $user
            ->carts()
            ->create([
                'status' => 'active',
                'expires_at' => null,
            ]);
    }

    private function guestTokenFromRequest(
        Request $request
    ): ?string {
        $cookieValue = $request->cookie(
            self::GUEST_COOKIE
        );

        if (
            ! is_string($cookieValue)
            || $cookieValue === ''
        ) {
            return null;
        }

        try {
            $token = Crypt::decryptString(
                $cookieValue
            );
        } catch (DecryptException) {
            return null;
        }

        if (! Str::isUuid($token)) {
            return null;
        }

        return $token;
    }

    private function purchasableVariantOrFail(
        int $variantId,
        bool $lock
    ): ProductVariant {
        $query = ProductVariant::query()
            ->whereKey(
                $variantId
            )
            ->where(
                'is_active',
                true
            )
            ->whereNull(
                'deleted_at'
            )
            ->whereHas(
                'product',
                fn ($query) => $query
                    ->published()
            )
            ->with([
                'product',
                'inventory',
            ]);

        if ($lock) {
            $query->lockForUpdate();
        }

        $variant = $query->first();

        if (
            $variant === null
            || $variant->inventory === null
        ) {
            throw ValidationException::withMessages([
                'product_variant_id' => [
                    'The selected product variant is not available.',
                ],
            ]);
        }

        return $variant;
    }

    private function isPurchasable(
        ?ProductVariant $variant
    ): bool {
        if (
            $variant === null
            || $variant->trashed()
            || ! $variant->is_active
            || $variant->inventory === null
            || $variant->product === null
            || $variant->product->trashed()
            || $variant->product->status !== 'active'
            || $variant->product->published_at === null
            || $variant->product->published_at->isFuture()
        ) {
            return false;
        }

        return true;
    }

    private function availableQuantity(
        ProductVariant $variant
    ): int {
        if ($variant->inventory === null) {
            return 0;
        }

        return max(
            0,
            $variant->inventory->on_hand_quantity
            - $variant->inventory->reserved_quantity
        );
    }

    private function validateQuantity(
        int $quantity,
        int $availableQuantity
    ): void {
        if ($quantity > 99) {
            throw ValidationException::withMessages([
                'quantity' => [
                    'The quantity may not be greater than 99.',
                ],
            ]);
        }

        if ($quantity > $availableQuantity) {
            throw ValidationException::withMessages([
                'quantity' => [
                    "Only {$availableQuantity} unit(s) are currently available.",
                ],
            ]);
        }
    }

    private function touchGuestCartExpiry(
        Cart $cart
    ): void {
        if ($cart->user_id !== null) {
            return;
        }

        $cart->expires_at = now()->addDays(
            self::GUEST_CART_TTL_DAYS
        );

        $cart->save();
    }

    private function hydrateItem(
        CartItem $item
    ): void {
        $variant = $item->variant;

        if ($variant === null) {
            $item->setAttribute(
                'is_available',
                false
            );

            $item->setAttribute(
                'available_quantity',
                0
            );

            $item->setAttribute(
                'unit_price',
                null
            );

            $item->setAttribute(
                'line_total',
                null
            );

            return;
        }

        $isPurchasable = $this->isPurchasable(
            $variant
        );

        $availableQuantity = $this
            ->availableQuantity(
                $variant
            );

        $unitPrice = $this->effectivePrice(
            $variant
        );

        $item->setAttribute(
            'is_available',
            $isPurchasable
            && $availableQuantity >= $item->quantity
        );

        $item->setAttribute(
            'available_quantity',
            $availableQuantity
        );

        $item->setAttribute(
            'unit_price',
            $unitPrice
        );

        $item->setAttribute(
            'line_total',
            $unitPrice === null
                ? null
                : $this->multiplyMoney(
                    $unitPrice,
                    $item->quantity
                )
        );
    }

    private function effectivePrice(
        ProductVariant $variant
    ): ?string {
        /*
         * Priorité:
         *
         * 1. promotion spécifique variante
         * 2. prix spécifique variante
         * 3. promotion produit
         * 4. prix de base produit
         */
        $price = $variant->sale_price
            ?? $variant->price
            ?? $variant->product?->sale_price
            ?? $variant->product?->base_price;

        if ($price === null) {
            return null;
        }

        return number_format(
            (float) $price,
            2,
            '.',
            ''
        );
    }

    private function multiplyMoney(
        string $amount,
        int $quantity
    ): string {
        [$whole, $decimal] = array_pad(
            explode(
                '.',
                $amount,
                2
            ),
            2,
            '0'
        );

        $decimal = str_pad(
            substr(
                $decimal,
                0,
                2
            ),
            2,
            '0'
        );

        $cents = (
            (int) $whole
            * 100
        ) + (int) $decimal;

        $totalCents = $cents
            * $quantity;

        return sprintf(
            '%d.%02d',
            intdiv(
                $totalCents,
                100
            ),
            $totalCents % 100
        );
    }
}
