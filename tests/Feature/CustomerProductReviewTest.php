<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_review_for_delivered_order_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Premium Saree']);
        $order = Order::factory()->for($user)->create([
            'status' => OrderStatus::Delivered,
            'customer_name' => 'Nadia Rahman',
            'customer_email' => 'nadia@example.test',
            'customer_phone' => '01712345678',
            'delivered_at' => now(),
        ]);
        $item = OrderItem::factory()->for($order)->for($product)->create(['product_name' => 'Premium Saree']);

        $response = $this->actingAs($user)->postJson('/api/customer/product-reviews', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Beautiful quality',
            'body' => 'Fabric quality and delivery experience were excellent.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', ProductReviewStatus::Pending->value)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.is_verified_purchase', true)
            ->assertJsonPath('data.product.name', 'Premium Saree')
            ->assertJsonPath('data.order_item.product_name', 'Premium Saree');

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $item->id,
            'status' => ProductReviewStatus::Pending->value,
            'is_verified_purchase' => true,
            'customer_phone' => '01712345678',
        ]);
    }

    public function test_customer_can_list_their_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = ProductReview::factory()->for($user)->create(['title' => 'My review']);
        ProductReview::factory()->for($otherUser)->create(['title' => 'Other review']);

        $response = $this->actingAs($user)->getJson('/api/customer/product-reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.title', 'My review');
    }

    public function test_customer_cannot_review_before_delivery(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Processing]);
        $item = OrderItem::factory()->for($order)->create();

        $response = $this->actingAs($user)->postJson('/api/customer/product-reviews', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'body' => 'This product is very good.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Reviews can only be submitted after delivery.');
    }

    public function test_customer_cannot_review_same_order_item_twice(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
        $item = OrderItem::factory()->for($order)->create();
        ProductReview::factory()->for($user)->for($item, 'orderItem')->create();

        $response = $this->actingAs($user)->postJson('/api/customer/product-reviews', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 4,
            'body' => 'This product is very good.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'You already reviewed this item.');
    }

    public function test_customer_cannot_review_another_customers_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($otherUser)->create(['status' => OrderStatus::Delivered]);
        $item = OrderItem::factory()->for($order)->create();

        $response = $this->actingAs($user)->postJson('/api/customer/product-reviews', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 4,
            'body' => 'This product is very good.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');
    }
}
