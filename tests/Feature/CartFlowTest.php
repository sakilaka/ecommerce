<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_products_to_cart_and_reuse_cart_session(): void
    {
        $product = Product::factory()->create([
            'name' => 'Wireless Earbuds',
            'price' => 249000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Default',
            'price' => 239000,
            'stock_quantity' => 3,
            'oversell_allowed' => true,
            'is_active' => true,
        ]);

        $firstResponse = $this->postJson('/api/storefront/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cartSessionId = $firstResponse->json('data.cart_session_id');

        $firstResponse->assertCreated()
            ->assertJsonPath('data.item_count', 1)
            ->assertJsonPath('data.subtotal', 239000)
            ->assertJsonPath('data.items.0.name', 'Wireless Earbuds');

        $secondResponse = $this->withHeader('X-Cart-Session', $cartSessionId)->postJson('/api/storefront/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $secondResponse->assertOk()
            ->assertJsonPath('data.cart_session_id', $cartSessionId)
            ->assertJsonPath('data.item_count', 3)
            ->assertJsonPath('data.subtotal', 717000)
            ->assertJsonPath('data.items.0.quantity', 3);
    }

    public function test_cart_quantity_can_be_updated_and_removed(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'cart-session-123']);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $updateResponse = $this->withHeader('X-Cart-Session', 'cart-session-123')->patchJson('/api/storefront/cart/items/'.$item->id, [
            'quantity' => 2,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal', 200000);

        $removeResponse = $this->withHeader('X-Cart-Session', 'cart-session-123')->deleteJson('/api/storefront/cart/items/'.$item->id);

        $removeResponse->assertOk()
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonPath('data.subtotal', 0);
    }

    public function test_cart_blocks_stock_when_variant_does_not_allow_overselling(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create([
            'stock_quantity' => 1,
            'reserved_quantity' => 0,
            'oversell_allowed' => false,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/storefront/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Requested quantity is not available.');
    }
}
