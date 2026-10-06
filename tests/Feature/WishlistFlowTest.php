<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_and_list_wishlist_items(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Galaxy A55 Official', 'sku' => 'GAL-A55']);
        $variant = ProductVariant::factory()->for($product)->create(['name' => 'Black / 128GB']);

        $response = $this->actingAs($customer)->postJson('/api/customer/wishlist', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'source' => 'product_page',
            'context' => ['section' => 'best_sellers'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.variant.id', $variant->id)
            ->assertJsonPath('data.source', 'product_page');

        $list = $this->actingAs($customer)->getJson('/api/customer/wishlist?q=galaxy');

        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.name', 'Galaxy A55 Official')
            ->assertJsonStructure(['data' => [['id', 'source', 'context', 'product', 'variant']], 'links', 'meta']);
    }

    public function test_customer_wishlist_add_is_idempotent_per_product(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($customer)->postJson('/api/customer/wishlist', ['product_id' => $product->id, 'source' => 'category_grid'])->assertCreated();
        $this->actingAs($customer)->postJson('/api/customer/wishlist', ['product_id' => $product->id, 'source' => 'search'])->assertOk();

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'source' => 'search',
        ]);
    }

    public function test_wishlist_rejects_variant_that_does_not_belong_to_product(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();
        $otherVariant = ProductVariant::factory()->create();

        $response = $this->actingAs($customer)->postJson('/api/customer/wishlist', [
            'product_id' => $product->id,
            'product_variant_id' => $otherVariant->id,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_customer_can_remove_only_own_wishlist_item(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $item = WishlistItem::factory()->for($customer)->create();
        $otherItem = WishlistItem::factory()->for($otherCustomer)->create();

        $this->actingAs($customer)->deleteJson('/api/customer/wishlist/'.$otherItem->id)->assertNotFound();
        $this->actingAs($customer)->deleteJson('/api/customer/wishlist/'.$item->id)->assertOk();

        $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
        $this->assertDatabaseHas('wishlist_items', ['id' => $otherItem->id]);
    }

    public function test_guest_cannot_manage_wishlist(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/api/customer/wishlist')->assertUnauthorized();
        $this->postJson('/api/customer/wishlist', ['product_id' => $product->id])->assertUnauthorized();
    }
}
