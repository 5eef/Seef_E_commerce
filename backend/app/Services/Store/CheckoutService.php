<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** @param array<string, mixed> $data */
    public function checkout(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data): Order {
            $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();

            if ($lockedCart->status !== 'active') {
                throw ValidationException::withMessages([
                    'cart' => ['This cart has already been checked out.'],
                ]);
            }

            $items = $lockedCart->items()->with([
                'variant' => fn ($query) => $query->withTrashed()->with(['product' => fn ($query) => $query->withTrashed(), 'optionValues']),
            ])->orderBy('id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => ['Your cart is empty.']]);
            }

            $preparedItems = [];
            $subtotalCents = 0;

            foreach ($items as $item) {
                $variant = $item->variant;
                $this->assertPurchasable($variant);

                $inventory = Inventory::query()
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory === null || $inventory->on_hand_quantity - $inventory->reserved_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => ["Insufficient stock for {$variant->product->name}."],
                    ]);
                }

                $unitPriceCents = $this->moneyToCents(
                    $variant->sale_price ?? $variant->price ?? $variant->product->sale_price ?? $variant->product->base_price
                );
                $lineCents = $unitPriceCents * $item->quantity;
                $subtotalCents += $lineCents;

                $preparedItems[] = compact('item', 'variant', 'inventory', 'unitPriceCents', 'lineCents');
            }

            $coupon = $this->resolveCoupon($data['coupon_code'] ?? null, $lockedCart->user_id, $subtotalCents);
            $discountCents = $this->discountCents($coupon, $subtotalCents);
            $shippingCents = 0;
            $taxCents = 0;
            $grandTotalCents = max(0, $subtotalCents - $discountCents + $shippingCents + $taxCents);
            $shippingAddress = $data['shipping_address'];
            $billingAddress = ($data['billing_same_as_shipping'] ?? true)
                ? $shippingAddress
                : ($data['billing_address'] ?? $shippingAddress);

            $order = Order::query()->create([
                'order_number' => $this->orderNumber(),
                'user_id' => $lockedCart->user_id,
                'coupon_id' => $coupon?->id,
                'customer_name' => trim($shippingAddress['first_name'].' '.$shippingAddress['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'currency' => 'MAD',
                'subtotal' => $this->centsToMoney($subtotalCents),
                'discount_total' => $this->centsToMoney($discountCents),
                'shipping_total' => $this->centsToMoney($shippingCents),
                'tax_total' => $this->centsToMoney($taxCents),
                'grand_total' => $this->centsToMoney($grandTotalCents),
                'coupon_code' => $coupon?->code,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'customer_note' => $data['customer_note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($preparedItems as $prepared) {
                $variant = $prepared['variant'];
                $inventory = $prepared['inventory'];
                $item = $prepared['item'];
                $before = $inventory->on_hand_quantity;
                $inventory->on_hand_quantity = $before - $item->quantity;
                $inventory->save();

                $orderItem = $order->items()->create([
                    'product_id' => $variant->product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'option_values' => $variant->optionValues->pluck('value')->values()->all(),
                    'unit_price' => $this->centsToMoney($prepared['unitPriceCents']),
                    'quantity' => $item->quantity,
                    'subtotal' => $this->centsToMoney($prepared['lineCents']),
                    'discount_total' => '0.00',
                    'tax_total' => '0.00',
                    'total' => $this->centsToMoney($prepared['lineCents']),
                    'unit_cost' => $variant->cost_price,
                ]);

                $inventory->movements()->create([
                    'balance' => 'on_hand',
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $inventory->on_hand_quantity,
                    'reference_type' => $orderItem->getMorphClass(),
                    'reference_id' => $orderItem->id,
                    'reason' => 'Checkout sale',
                    'user_id' => $lockedCart->user_id,
                ]);
            }

            if ($coupon !== null) {
                $coupon->usages()->create([
                    'order_id' => $order->id,
                    'user_id' => $lockedCart->user_id,
                    'discount_amount' => $this->centsToMoney($discountCents),
                    'used_at' => now(),
                ]);
            }

            $order->payments()->create([
                'method' => $data['payment_method'],
                'provider' => null,
                'status' => 'pending',
                'amount' => $this->centsToMoney($grandTotalCents),
                'currency' => 'MAD',
                'metadata' => ['mode' => 'development_pending'],
            ]);
            $order->shipments()->create(['status' => 'pending', 'shipping_cost' => '0.00']);
            $order->statusHistories()->create(['from_status' => null, 'to_status' => 'pending', 'changed_by' => $lockedCart->user_id]);

            $lockedCart->items()->delete();
            $lockedCart->update([
                'status' => 'converted',
                'guest_token' => null,
                'expires_at' => $lockedCart->user_id === null ? now() : null,
            ]);

            return $order->load(['items', 'payments', 'shipments', 'statusHistories']);
        }, 3);
    }

    private function assertPurchasable(?ProductVariant $variant): void
    {
        if ($variant === null || $variant->trashed() || ! $variant->is_active || $variant->product === null || $variant->product->trashed() || $variant->product->status !== 'active' || $variant->product->published_at === null || $variant->product->published_at->isFuture()) {
            throw ValidationException::withMessages(['cart' => ['Your cart contains an unavailable product.']]);
        }
    }

    private function resolveCoupon(?string $code, ?int $userId, int $subtotalCents): ?Coupon
    {
        if ($code === null || $code === '') {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)->lockForUpdate()->first();

        if ($coupon === null || ! $coupon->is_active || ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) || ($coupon->ends_at !== null && $coupon->ends_at->isPast())) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon is not valid.']]);
        }

        if ($coupon->minimum_order_amount !== null && $subtotalCents < $this->moneyToCents($coupon->minimum_order_amount)) {
            throw ValidationException::withMessages(['coupon_code' => ['The order does not meet this coupon minimum.']]);
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon usage limit has been reached.']]);
        }

        if ($coupon->usage_limit_per_user !== null) {
            if ($userId === null || $coupon->usages()->where('user_id', $userId)->count() >= $coupon->usage_limit_per_user) {
                throw ValidationException::withMessages(['coupon_code' => ['This coupon cannot be used by this customer.']]);
            }
        }

        return $coupon;
    }

    private function discountCents(?Coupon $coupon, int $subtotalCents): int
    {
        if ($coupon === null) {
            return 0;
        }

        $discount = $coupon->type === 'percentage'
            ? intdiv($subtotalCents * $this->moneyToCents($coupon->value), 10000)
            : $this->moneyToCents($coupon->value);

        if ($coupon->maximum_discount_amount !== null) {
            $discount = min($discount, $this->moneyToCents($coupon->maximum_discount_amount));
        }

        return min($subtotalCents, $discount);
    }

    private function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^(?<sign>-?)(?<whole>\d+)(?:\.(?<decimal>\d{1,2}))?$/', $normalized, $matches)) {
            throw new \InvalidArgumentException('Invalid monetary amount.');
        }

        $cents = ((int) $matches['whole'] * 100)
            + (int) str_pad($matches['decimal'] ?? '', 2, '0');

        return ($matches['sign'] ?? '') === '-' ? -$cents : $cents;
    }

    private function centsToMoney(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function orderNumber(): string
    {
        do {
            $number = 'SEEF-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
