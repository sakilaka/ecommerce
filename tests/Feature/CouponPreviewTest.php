<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Enums\DiscountType;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_preview_valid_coupon_discount(): void
    {
        $this->createCart('coupon-cart-1', 200000);
        Coupon::factory()->create([
            'code' => 'EID10',
            'type' => DiscountType::Percent,
            'value' => 10,
            'minimum_order_amount' => 100000,
            'maximum_discount_amount' => 30000,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Cart-Session', 'coupon-cart-1')->postJson('/api/storefront/coupon-preview', [
            'coupon_code' => 'eid10',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'EID10')
            ->assertJsonPath('data.discount_total', 20000)
            ->assertJsonPath('data.subtotal_after_discount', 180000);
    }

    public function test_coupon_preview_rejects_invalid_coupon(): void
    {
        $this->createCart('coupon-cart-2', 200000);

        $response = $this->withHeader('X-Cart-Session', 'coupon-cart-2')->postJson('/api/storefront/coupon-preview', [
            'coupon_code' => 'MISSING',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Coupon is not valid for this cart.');
    }

    public function test_customer_coupon_preview_respects_per_customer_limit(): void
    {
        $user = User::factory()->create();
        $cart = $this->createCart(null, 200000, $user);
        $coupon = Coupon::factory()->create([
            'code' => 'ONCE',
            'type' => DiscountType::Fixed,
            'value' => 25000,
            'minimum_order_amount' => 0,
            'per_customer_limit' => 1,
            'is_active' => true,
        ]);
        Order::factory()->for($user)->for($coupon)->create();

        $response = $this->actingAs($user)->postJson('/storefront/coupon-preview', [
            'cart_session_id' => $cart->session_id,
            'coupon_code' => 'ONCE',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Coupon is not valid for this cart.');
    }

    private function createCart(?string $sessionId, int $unitPrice, ?User $user = null): Cart
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => $unitPrice,
            'is_active' => true,
        ]);
        $cart = Cart::factory()->create([
            'user_id' => $user?->id,
            'session_id' => $sessionId ?? 'customer-coupon-cart',
            'status' => CartStatus::Active,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'price_snapshot' => ['unit_price' => $unitPrice],
        ]);

        return $cart;
    }
}
