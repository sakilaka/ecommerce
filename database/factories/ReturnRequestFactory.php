<?php

namespace Database\Factories;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'order_item_id' => OrderItem::factory(),
            'user_id' => User::factory(),
            'return_number' => 'RET-'.Str::upper(Str::random(8)),
            'status' => ReturnStatus::Requested->value,
            'refund_amount' => fake()->numberBetween(0, 100000),
            'reason' => fake()->sentence(),
            'admin_note' => null,
        ];
    }
}
