<?php

namespace Database\Factories;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office']),
            'name' => fake()->name(),
            'phone' => '01'.fake()->numerify('#########'),
            'district' => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna']),
            'area' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'address_line' => fake()->address(),
            'is_default' => false,
        ];
    }
}
