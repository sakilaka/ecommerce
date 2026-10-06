<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('SAVE##??')),
            'type' => fake()->randomElement([DiscountType::Fixed->value, DiscountType::Percent->value]),
            'value' => fake()->numberBetween(5, 50000),
            'minimum_order_amount' => fake()->numberBetween(0, 200000),
            'maximum_discount_amount' => fake()->numberBetween(5000, 100000),
            'usage_limit' => fake()->numberBetween(50, 500),
            'used_count' => 0,
            'per_customer_limit' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
