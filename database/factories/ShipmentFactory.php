<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCod = fake()->boolean(70);
        $deliveryCharge = fake()->numberBetween(6000, 15000);
        $codCharge = $isCod ? fake()->numberBetween(0, 3000) : 0;
        $codAmount = $isCod ? fake()->numberBetween(100000, 5000000) : 0;

        return [
            'order_id' => Order::factory(['payment_method' => $isCod ? PaymentMethod::CashOnDelivery : PaymentMethod::Bkash]),
            'return_request_id' => null,
            'direction' => 'outbound',
            'status' => ShipmentStatus::Pending->value,
            'courier_name' => fake()->optional()->randomElement(['Pathao Courier', 'Steadfast', 'RedX']),
            'tracking_number' => fake()->optional()->bothify('TRK-########'),
            'tracking_url' => null,
            'delivery_charge' => $deliveryCharge,
            'cod_charge' => $codCharge,
            'cod_amount' => $codAmount,
            'recipient_name' => fake()->name(),
            'recipient_phone' => '01'.fake()->numerify('#########'),
            'recipient_district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'recipient_area' => fake()->city(),
            'recipient_address' => fake()->address(),
            'estimated_delivery_at' => now()->addDays(fake()->numberBetween(1, 5)),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
