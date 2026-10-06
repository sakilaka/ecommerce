<?php

namespace Database\Factories;

use App\Enums\InventoryAdjustmentType;
use App\Models\InventoryAdjustment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAdjustment>
 */
class InventoryAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $delta = fake()->numberBetween(-5, 25);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'user_id' => User::factory(),
            'type' => InventoryAdjustmentType::Manual->value,
            'quantity_delta' => $delta,
            'stock_after' => max(0, $delta + fake()->numberBetween(0, 100)),
            'reference_type' => null,
            'reference_id' => null,
            'note' => fake()->sentence(),
        ];
    }
}
