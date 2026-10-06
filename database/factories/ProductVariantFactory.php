<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Default', 'Black / M', 'Blue / 128GB', 'Large']),
            'sku' => fake()->unique()->bothify('VAR-####-????'),
            'options' => ['color' => fake()->safeColorName()],
            'price' => null,
            'compare_at_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
            'oversell_allowed' => true,
            'is_active' => true,
        ];
    }
}
