<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(100000, 5000000);
        $shipping = fake()->numberBetween(6000, 15000);
        $discount = fake()->numberBetween(0, 50000);

        return [
            'user_id' => User::factory(),
            'coupon_id' => Coupon::factory(),
            'order_number' => 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '01'.fake()->numerify('#########'),
            'shipping_address' => ['address_line' => fake()->address()],
            'shipping_district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'shipping_area' => fake()->city(),
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'shipping_total' => $shipping,
            'tax_total' => 0,
            'grand_total' => $subtotal + $shipping - $discount,
            'customer_note' => null,
        ];
    }
}
