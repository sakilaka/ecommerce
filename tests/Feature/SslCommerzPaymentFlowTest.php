<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SslCommerzPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_initiate_configured_sslcommerz_payment(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/*' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/pay/session-123',
                'sessionkey' => 'session-123',
            ]),
        ]);
        config()->set('payment_gateways.sslcommerz.store_id', 'test-store');
        config()->set('payment_gateways.sslcommerz.store_password', 'test-password');
        $order = Order::factory()->create([
            'order_number' => 'ORD-SSL-1001',
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
            'customer_name' => 'Sakil Ahmed',
            'customer_email' => 'sakil@example.com',
            'customer_phone' => '01712345678',
            'shipping_address' => ['address_line' => 'Mirpur DOHS'],
            'shipping_district' => 'Dhaka',
            'grand_total' => 125000,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'amount' => 125000,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => null,
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/initiate', [
            'order_number' => 'ORD-SSL-1001',
            'customer_phone' => '01712345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.order_number', 'ORD-SSL-1001')
            ->assertJsonPath('data.redirect_url', 'https://sandbox.sslcommerz.com/pay/session-123')
            ->assertJsonPath('data.gateway', 'sslcommerz');

        $payment->refresh();
        $this->assertSame('sslcommerz', $payment->gateway);
        $this->assertNotNull($payment->gateway_transaction_id);
        $this->assertSame('session-123', $payment->gateway_payload['sslcommerz']['sessionkey']);
        $this->assertSame('https://sandbox.sslcommerz.com/pay/session-123', $payment->gateway_payload['sslcommerz']['redirect_url']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            && $request['store_id'] === 'test-store'
            && $request['total_amount'] === '1250.00'
            && $request['currency'] === 'BDT'
            && $request['cus_phone'] === '01712345678');
    }

    public function test_sslcommerz_initiation_requires_matching_order_identity(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-SSL-1002',
            'customer_phone' => '01712345678',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sslcommerz',
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/initiate', [
            'order_number' => 'ORD-SSL-1002',
            'customer_phone' => '01999999999',
        ]);

        $response->assertNotFound();
    }

    public function test_sslcommerz_initiation_fails_when_credentials_are_missing(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-SSL-1003',
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
            'customer_phone' => '01712345678',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sslcommerz',
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/initiate', [
            'order_number' => 'ORD-SSL-1003',
            'customer_phone' => '01712345678',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'SSLCommerz is enabled but gateway credentials are not configured.');
    }

    public function test_sslcommerz_fail_callback_marks_payment_failed(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-SSL-FAIL',
        ]);

        $response = $this->postJson('/api/storefront/payments/sslcommerz/fail', [
            'tran_id' => 'AUR-SSL-FAIL',
            'error' => 'Transaction failed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::Failed->value)
            ->assertJsonPath('data.event', 'failed');

        $payment->refresh();
        $order->refresh();
        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(PaymentStatus::Failed, $order->payment_status);
        $this->assertSame('failed', $payment->gateway_payload['sslcommerz_callback']['event']);
    }

    public function test_sslcommerz_success_callback_validates_transaction_before_marking_payment_paid(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'AUR-SSL-SUCCESS',
                'val_id' => 'VALIDATION-ID-1',
                'amount' => '1250.00',
                'currency' => 'BDT',
                'bank_tran_id' => 'BANK-TRX-1',
            ]),
        ]);
        config()->set('payment_gateways.sslcommerz.store_id', 'test-store');
        config()->set('payment_gateways.sslcommerz.store_password', 'test-password');
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'amount' => 125000,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-SSL-SUCCESS',
        ]);

        $response = $this->postJson('/api/storefront/payments/sslcommerz/success', [
            'tran_id' => 'AUR-SSL-SUCCESS',
            'val_id' => 'VALIDATION-ID-1',
            'store_passwd' => 'sensitive-value',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::Paid->value)
            ->assertJsonPath('data.event', 'success_validated');

        $payment->refresh();
        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('success_validated', $payment->gateway_payload['sslcommerz_callback']['event']);
        $this->assertSame('BANK-TRX-1', $payment->gateway_payload['sslcommerz_callback']['validation']['bank_tran_id']);
        $this->assertArrayNotHasKey('store_passwd', $payment->gateway_payload['sslcommerz_callback']['payload']);

        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php')
            && $request['val_id'] === 'VALIDATION-ID-1'
            && $request['store_id'] === 'test-store'
            && $request['format'] === 'json');
    }

    public function test_sslcommerz_success_callback_rejects_amount_mismatch(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'AUR-SSL-MISMATCH',
                'val_id' => 'VALIDATION-ID-2',
                'amount' => '1200.00',
                'currency' => 'BDT',
            ]),
        ]);
        config()->set('payment_gateways.sslcommerz.store_id', 'test-store');
        config()->set('payment_gateways.sslcommerz.store_password', 'test-password');
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'amount' => 125000,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-SSL-MISMATCH',
        ]);

        $response = $this->postJson('/api/storefront/payments/sslcommerz/success', [
            'tran_id' => 'AUR-SSL-MISMATCH',
            'val_id' => 'VALIDATION-ID-2',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'SSLCommerz validation amount does not match this order.');

        $payment->refresh();
        $order->refresh();
        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(PaymentStatus::Failed, $order->payment_status);
        $this->assertNull($payment->paid_at);
        $this->assertSame('validation_failed', $payment->gateway_payload['sslcommerz_callback']['event']);
    }

    public function test_sslcommerz_success_callback_requires_validation_id(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
        ]);
        Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-SSL-NO-VAL',
        ]);

        $response = $this->postJson('/api/storefront/payments/sslcommerz/success', [
            'tran_id' => 'AUR-SSL-NO-VAL',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Missing SSLCommerz validation id.');
    }
}
