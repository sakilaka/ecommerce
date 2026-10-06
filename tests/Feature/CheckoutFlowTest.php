<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ShipmentStatus;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_place_cash_on_delivery_order_from_cart(): void
    {
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 0,
            'cod_available' => true,
            'is_active' => true,
        ]);
        $coupon = Coupon::factory()->create([
            'code' => 'EID10',
            'type' => DiscountType::Percent,
            'value' => 10,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => 50000,
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'name' => 'Premium Panjabi',
            'price' => 200000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
            'is_refundable' => true,
            'return_window_days' => 7,
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Maroon / M',
            'sku' => 'PAN-MRN-M',
            'price' => 180000,
            'stock_quantity' => 5,
            'reserved_quantity' => 0,
            'oversell_allowed' => false,
            'is_active' => true,
        ]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'checkout-cart-1', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 180000,
            'price_snapshot' => ['unit_price' => 180000],
        ]);

        $response = $this->withHeader('X-Cart-Session', 'checkout-cart-1')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'coupon_code' => 'EID10',
            'customer' => [
                'name' => 'Sakil Ahmed',
                'email' => 'sakil@example.com',
                'phone' => '01712345678',
            ],
            'shipping' => [
                'label' => 'Home',
                'district' => 'Dhaka',
                'area' => 'Inside Dhaka',
                'postal_code' => '1216',
                'address_line' => 'House 12, Road 4, Mirpur',
            ],
            'customer_note' => 'Call before delivery.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::Pending->value)
            ->assertJsonPath('data.payment_method', PaymentMethod::CashOnDelivery->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Unpaid->value)
            ->assertJsonPath('data.subtotal', 360000)
            ->assertJsonPath('data.discount_total', 36000)
            ->assertJsonPath('data.shipping_total', 8000)
            ->assertJsonPath('data.grand_total', 332000)
            ->assertJsonPath('data.items.0.product_name', 'Premium Panjabi')
            ->assertJsonPath('data.payments.0.amount', 332000)
            ->assertJsonPath('data.shipments.0.status', ShipmentStatus::Pending->value)
            ->assertJsonPath('data.shipments.0.cod_amount', 332000)
            ->assertJsonPath('data.shipments.0.recipient_district', 'Dhaka')
            ->assertJsonPath('data.shipments.0.events.0.event_type', 'order_placed');

        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'status' => CartStatus::Ordered->value]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 3]);
        $this->assertDatabaseHas('inventory_adjustments', ['product_variant_id' => $variant->id, 'quantity_delta' => -2]);
        $this->assertDatabaseHas('shipments', [
            'status' => ShipmentStatus::Pending->value,
            'recipient_name' => 'Sakil Ahmed',
            'recipient_phone' => '01712345678',
            'recipient_district' => 'Dhaka',
            'recipient_area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 0,
            'cod_amount' => 332000,
        ]);
        $this->assertDatabaseHas('shipment_events', [
            'status' => ShipmentStatus::Pending->value,
            'event_type' => 'order_placed',
            'location' => 'Dhaka',
            'message' => 'Order received and delivery preparation started.',
            'is_public' => true,
        ]);
    }

    public function test_checkout_rejects_manual_mobile_payment_without_merchant_number(): void
    {
        config()->set('payment_methods.methods.bkash.enabled', true);
        config()->set('payment_methods.methods.bkash.merchant_number', null);
        $this->createCheckoutCart('missing-bkash-number-cart');

        $response = $this->withHeader('X-Cart-Session', 'missing-bkash-number-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::Bkash->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'bKash is not ready for checkout yet.');

        $this->assertDatabaseMissing('orders', ['customer_phone' => '01712345678']);
    }

    public function test_checkout_rejects_sslcommerz_when_gateway_credentials_are_missing(): void
    {
        config()->set('payment_methods.methods.sslcommerz.enabled', true);
        config()->set('payment_gateways.sslcommerz.store_id', null);
        config()->set('payment_gateways.sslcommerz.store_password', null);
        $this->createCheckoutCart('missing-sslcommerz-config-cart');

        $response = $this->withHeader('X-Cart-Session', 'missing-sslcommerz-config-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::SslCommerz->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'SSLCommerz is not ready for checkout yet.');

        $this->assertDatabaseMissing('orders', ['customer_phone' => '01712345678']);
    }

    private function createCheckoutCart(string $sessionId): Cart
    {
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 0,
            'cod_available' => true,
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => $sessionId, 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        return $cart;
    }

    public function test_customer_can_checkout_with_saved_delivery_address(): void
    {
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Mirpur',
            'delivery_charge' => 7000,
            'cod_charge' => 1000,
            'cod_available' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Sakil Ahmed',
            'email' => 'sakil@example.com',
            'phone' => '01712345678',
        ]);
        $address = CustomerAddress::factory()->for($user)->create([
            'label' => 'Home',
            'name' => 'Sakil Ahmed',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'area' => 'Mirpur',
            'postal_code' => '1216',
            'address_line' => 'House 12, Road 4',
            'is_default' => true,
        ]);
        $product = Product::factory()->create([
            'name' => 'Premium Saree',
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 150000,
            'stock_quantity' => 4,
            'reserved_quantity' => 0,
            'oversell_allowed' => false,
            'is_active' => true,
        ]);
        $cart = Cart::factory()->for($user)->create(['status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 150000,
            'price_snapshot' => ['unit_price' => 150000],
        ]);

        $response = $this->actingAs($user)->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer_address_id' => $address->id,
            'customer' => [
                'name' => 'Sakil Ahmed',
                'email' => 'sakil@example.com',
                'phone' => '01712345678',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_address_id', $address->id)
            ->assertJsonPath('data.shipping_address.address_line', 'House 12, Road 4')
            ->assertJsonPath('data.shipping_total', 8000)
            ->assertJsonPath('data.grand_total', 158000)
            ->assertJsonPath('data.shipments.0.delivery_charge', 7000)
            ->assertJsonPath('data.shipments.0.cod_charge', 1000)
            ->assertJsonPath('data.shipments.0.cod_amount', 158000);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_address_id' => $address->id,
            'shipping_district' => 'Dhaka',
            'shipping_area' => 'Mirpur',
        ]);
    }

    public function test_customer_cannot_checkout_with_another_customers_saved_address(): void
    {
        $user = User::factory()->create();
        $address = CustomerAddress::factory()->create();
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->for($user)->create(['status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $response = $this->actingAs($user)->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer_address_id' => $address->id,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_address_id']);
    }

    public function test_checkout_rejects_invalid_coupon_code(): void
    {
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 0,
            'cod_available' => true,
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'invalid-coupon-cart', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $response = $this->withHeader('X-Cart-Session', 'invalid-coupon-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'coupon_code' => 'MISSING',
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Coupon is not valid for this cart.');
    }

    public function test_checkout_rejects_unavailable_card_payment_method(): void
    {
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 0,
            'cod_available' => true,
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'disabled-payment-cart', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $response = $this->withHeader('X-Cart-Session', 'disabled-payment-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::Card->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Card Payment is not available for checkout yet.');

        $this->assertDatabaseMissing('orders', ['customer_phone' => '01712345678']);
    }

    public function test_checkout_can_place_manual_mobile_payment_order_with_instructions(): void
    {
        config()->set('payment_methods.methods.bkash.enabled', true);
        config()->set('payment_methods.methods.bkash.merchant_number', '01799999999');
        DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 1500,
            'cod_available' => false,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'name' => 'Wireless Earbuds',
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 250000,
            'stock_quantity' => 3,
            'reserved_quantity' => 0,
            'oversell_allowed' => false,
            'is_active' => true,
        ]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'bkash-checkout-cart', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 250000,
            'price_snapshot' => ['unit_price' => 250000],
        ]);

        $response = $this->withHeader('X-Cart-Session', 'bkash-checkout-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::Bkash->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01812345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', PaymentMethod::Bkash->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.shipping_total', 8000)
            ->assertJsonPath('data.grand_total', 258000)
            ->assertJsonPath('data.payments.0.method', PaymentMethod::Bkash->value)
            ->assertJsonPath('data.payments.0.status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.payments.0.gateway', 'bkash')
            ->assertJsonPath('data.payments.0.gateway_payload.next_action', 'manual_payment_instructions')
            ->assertJsonPath('data.payments.0.gateway_payload.instruction_type', 'manual_mobile_payment')
            ->assertJsonPath('data.payments.0.gateway_payload.merchant_number', '01799999999')
            ->assertJsonPath('data.payments.0.gateway_payload.reference', fn (string $value): bool => str_starts_with($value, 'ORD-'))
            ->assertJsonPath('data.payments.0.gateway_payload.amount', 258000);

        $this->assertDatabaseHas('payments', [
            'method' => PaymentMethod::Bkash->value,
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'bkash',
            'amount' => 258000,
        ]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 2]);
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        Cart::factory()->create(['user_id' => null, 'session_id' => 'empty-cart', 'status' => CartStatus::Active]);

        $response = $this->withHeader('X-Cart-Session', 'empty-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'address_line' => 'Mirpur'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Your cart is empty.');
    }

    public function test_checkout_requires_supported_delivery_zone(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => true]);
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'unsupported-zone-cart', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $response = $this->withHeader('X-Cart-Session', 'unsupported-zone-cart')->postJson('/api/storefront/checkout', [
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'customer' => ['name' => 'Sakil Ahmed', 'phone' => '01712345678'],
            'shipping' => ['district' => 'Barishal', 'area' => 'Sadar', 'address_line' => 'Main road'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Delivery is not available for this district or area yet.');
    }
}
