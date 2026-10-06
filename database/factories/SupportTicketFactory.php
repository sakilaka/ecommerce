<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'ticket_number' => 'SUP-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'topic' => fake()->randomElement(['order_help', 'delivery', 'return_refund', 'payment', 'product', 'account']),
            'priority' => fake()->randomElement(['normal', 'high']),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved']),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraph(),
            'admin_reply' => null,
            'resolved_at' => null,
        ];
    }
}
