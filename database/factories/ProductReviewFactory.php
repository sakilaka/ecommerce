<?php

namespace Database\Factories;

use App\Enums\ProductReviewStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'order_item_id' => null,
            'moderated_by' => null,
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => ProductReviewStatus::Pending->value,
            'is_verified_purchase' => false,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '01'.fake()->numerify('#########'),
            'admin_note' => null,
            'moderated_at' => null,
            'helpful_count' => fake()->numberBetween(0, 25),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductReviewStatus::Approved->value,
            'moderated_at' => now(),
        ]);
    }

    public function verifiedPurchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_item_id' => OrderItem::factory(),
            'is_verified_purchase' => true,
        ]);
    }
}
