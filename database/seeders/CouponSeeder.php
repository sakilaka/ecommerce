<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'EID50',
                'type' => DiscountType::Percent->value,
                'value' => 50,
                'minimum_order_amount' => 199900,
                'maximum_discount_amount' => 100000,
                'usage_limit' => 500,
                'per_customer_limit' => 1,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(21),
            ],
            [
                'code' => 'RAMADAN10',
                'type' => DiscountType::Percent->value,
                'value' => 10,
                'minimum_order_amount' => 99900,
                'maximum_discount_amount' => 50000,
                'usage_limit' => 1000,
                'per_customer_limit' => 2,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
            ],
            [
                'code' => 'BOISHAKH20',
                'type' => DiscountType::Percent->value,
                'value' => 20,
                'minimum_order_amount' => 149900,
                'maximum_discount_amount' => 75000,
                'usage_limit' => 700,
                'per_customer_limit' => 1,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->updateOrCreate(
                ['code' => $coupon['code']],
                array_merge([
                    'used_count' => 0,
                    'is_active' => true,
                ], $coupon),
            );
        }
    }
}
