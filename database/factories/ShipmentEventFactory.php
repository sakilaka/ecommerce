<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentEvent>
 */
class ShipmentEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'user_id' => User::factory()->admin(),
            'status' => ShipmentStatus::Pending->value,
            'event_type' => 'status_update',
            'location' => fake()->optional()->randomElement(['Dhaka Hub', 'Chattogram Hub', 'Courier Office']),
            'message' => fake()->optional()->sentence(),
            'is_public' => true,
            'metadata' => ['source' => 'factory'],
            'occurred_at' => now(),
        ];
    }
}
