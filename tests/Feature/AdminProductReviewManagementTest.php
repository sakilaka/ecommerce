<?php

namespace Tests\Feature;

use App\Enums\ProductReviewStatus;
use App\Models\AuditLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_product_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Galaxy A55 Official', 'sku' => 'GAL-A55']);
        $review = ProductReview::factory()->for($product)->approved()->create([
            'rating' => 5,
            'title' => 'Excellent phone',
            'body' => 'Battery backup and display quality are strong.',
            'customer_name' => 'Sakil Ahmed',
            'is_verified_purchase' => true,
        ]);
        ProductReview::factory()->create(['status' => ProductReviewStatus::Pending, 'rating' => 3]);

        $response = $this->actingAs($admin)->getJson('/api/admin/reviews?q=galaxy&status=approved&rating=5&verified=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $review->id)
            ->assertJsonPath('data.0.status', 'approved')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.is_verified_purchase', true)
            ->assertJsonPath('data.0.product.name', 'Galaxy A55 Official')
            ->assertJsonStructure(['data' => [['id', 'rating', 'title', 'body', 'status', 'is_verified_purchase', 'product', 'customer']], 'links', 'meta']);
    }

    public function test_admin_can_show_review_details(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Premium Panjabi']);
        $orderItem = OrderItem::factory()->for($product)->create(['product_name' => 'Premium Panjabi']);
        $review = ProductReview::factory()->for($product)->for($orderItem, 'orderItem')->create(['title' => 'Great fabric']);

        $response = $this->actingAs($admin)->getJson('/api/admin/reviews/'.$review->id);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Great fabric')
            ->assertJsonPath('data.product.name', 'Premium Panjabi')
            ->assertJsonPath('data.order_item.product_name', 'Premium Panjabi');
    }

    public function test_admin_can_moderate_review_and_audit_the_change(): void
    {
        $admin = User::factory()->admin()->create();
        $review = ProductReview::factory()->create([
            'status' => ProductReviewStatus::Pending,
            'is_verified_purchase' => false,
            'admin_note' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/reviews/'.$review->id, [
            'status' => 'approved',
            'is_verified_purchase' => true,
            'admin_note' => 'Matched delivered order item.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.is_verified_purchase', true)
            ->assertJsonPath('data.admin_note', 'Matched delivered order item.')
            ->assertJsonPath('data.moderator.id', $admin->id);

        $review->refresh();
        $this->assertSame(ProductReviewStatus::Approved, $review->status);
        $this->assertTrue($review->is_verified_purchase);
        $this->assertSame($admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'product_review.updated',
            'auditable_type' => ProductReview::class,
            'auditable_id' => $review->id,
        ]);
        $this->assertSame('approved', AuditLog::query()->where('event', 'product_review.updated')->first()?->new_values['status']);
    }

    public function test_review_update_rejects_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();
        $review = ProductReview::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/reviews/'.$review->id, [
            'status' => 'published',
            'is_verified_purchase' => 'maybe',
            'admin_note' => str_repeat('x', 1001),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'is_verified_purchase', 'admin_note']);
    }

    public function test_customer_cannot_manage_admin_reviews(): void
    {
        $customer = User::factory()->create();
        $review = ProductReview::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/admin/reviews/'.$review->id)->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/reviews/'.$review->id, [
            'status' => 'approved',
            'is_verified_purchase' => true,
        ])->assertForbidden();
    }
}
