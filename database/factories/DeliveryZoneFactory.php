<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'area' => fake()->city(),
            'delivery_charge' => fake()->numberBetween(6000, 15000),
            'cod_charge' => 0,
            'estimated_min_days' => 1,
            'estimated_max_days' => 5,
            'cod_available' => true,
            'is_active' => true,
        ];
    }
}
