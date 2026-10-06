<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::CashOnDelivery->value,
            'status' => PaymentStatus::Pending->value,
            'amount' => fake()->numberBetween(100000, 5000000),
            'gateway' => null,
            'gateway_transaction_id' => null,
            'gateway_payload' => null,
            'paid_at' => null,
        ];
    }
}
