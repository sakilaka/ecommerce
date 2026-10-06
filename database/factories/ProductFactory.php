<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->numberBetween(49900, 2500000);

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'status' => ProductStatus::Active->value,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => $price,
            'compare_at_price' => $price + fake()->numberBetween(10000, 500000),
            'cost_price' => max(1, $price - fake()->numberBetween(5000, 200000)),
            'discount_percent' => fake()->optional()->numberBetween(5, 40),
            'sale_starts_at' => null,
            'sale_ends_at' => null,
            'is_featured' => fake()->boolean(25),
            'is_refundable' => true,
            'return_window_days' => 7,
            'weight_kg' => fake()->randomFloat(3, 0.1, 10),
            'meta' => [],
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Draft->value,
            'published_at' => null,
        ]);
    }
}
