<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRefund>
 */
class PaymentRefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'order_id' => Order::factory(),
            'user_id' => User::factory()->admin(),
            'amount' => fake()->numberBetween(10000, 200000),
            'method' => fake()->randomElement(['manual', 'bkash', 'nagad', 'bank']),
            'status' => 'recorded',
            'reference' => fake()->optional()->bothify('RF-####??'),
            'reason' => fake()->sentence(),
            'admin_note' => fake()->optional()->sentence(),
            'refunded_at' => now(),
        ];
    }
}
