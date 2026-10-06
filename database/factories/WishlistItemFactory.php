<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'source' => fake()->randomElement(['product_page', 'category_grid', 'recommended', 'search']),
            'context' => [],
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => ProductVariant::factory(),
        ]);
    }
}
