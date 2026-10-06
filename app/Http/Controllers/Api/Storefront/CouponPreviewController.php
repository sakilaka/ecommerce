<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\PreviewCouponRequest;
use App\Models\Cart;
use App\Services\CouponQuoteService;
use Illuminate\Http\JsonResponse;

class CouponPreviewController extends Controller
{
    public function store(PreviewCouponRequest $request, CouponQuoteService $coupons): JsonResponse
    {
        $validated = $request->validated();
        $cart = $this->findActiveCart($request->user()?->id, $validated['cart_session_id'] ?? $request->header('X-Cart-Session'));

        if (! $cart || $cart->items()->count() === 0) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $cart->load('items');
        $subtotal = $cart->items->sum(fn ($item): int => $item->unit_price * $item->quantity);
        $coupon = $coupons->resolve($validated['coupon_code'], $subtotal, $request->user()?->id);

        if (! $coupon) {
            return response()->json(['message' => 'Coupon is not valid for this cart.'], 422);
        }

        $discount = $coupons->discount($coupon, $subtotal);

        return response()->json([
            'data' => [
                'code' => $coupon->code,
                'type' => $coupon->type->value,
                'value' => $coupon->value,
                'minimum_order_amount' => $coupon->minimum_order_amount,
                'maximum_discount_amount' => $coupon->maximum_discount_amount,
                'discount_total' => $discount,
                'subtotal_after_discount' => max(0, $subtotal - $discount),
            ],
        ]);
    }

    private function findActiveCart(?int $userId, ?string $sessionId): ?Cart
    {
        return Cart::query()
            ->where('status', CartStatus::Active)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when(! $userId, fn ($query) => $query->where('session_id', $sessionId))
            ->first();
    }
}
