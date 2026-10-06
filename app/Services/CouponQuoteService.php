<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Str;

class CouponQuoteService
{
    public function resolve(string $code, int $subtotal, ?int $userId = null): ?Coupon
    {
        $coupon = Coupon::query()
            ->where('code', Str::upper($code))
            ->where('is_active', true)
            ->where('minimum_order_amount', '<=', $subtotal)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->first();

        if (! $coupon || ! $userId || ! $coupon->per_customer_limit) {
            return $coupon;
        }

        $usedByCustomer = Order::query()
            ->where('user_id', $userId)
            ->where('coupon_id', $coupon->id)
            ->count();

        return $usedByCustomer < $coupon->per_customer_limit ? $coupon : null;
    }

    public function discount(Coupon $coupon, int $subtotal): int
    {
        $discount = $coupon->type === DiscountType::Percent
            ? (int) floor($subtotal * ($coupon->value / 100))
            : $coupon->value;

        if ($coupon->maximum_discount_amount) {
            $discount = min($discount, $coupon->maximum_discount_amount);
        }

        return min($discount, $subtotal);
    }
}
