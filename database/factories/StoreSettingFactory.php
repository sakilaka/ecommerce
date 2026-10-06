<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'setting_group' => fake()->randomElement(['identity', 'delivery', 'checkout', 'returns', 'operations']),
            'key' => fake()->unique()->slug(3),
            'value' => ['value' => fake()->word()],
        ];
    }
}
