<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentResultPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_result_page_is_available(): void
    {
        $this->get('/payment/result')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_customer_can_fetch_payment_result_by_transaction_id(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-RESULT-1001',
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 184000,
        ]);
        Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Paid,
            'amount' => 184000,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-RESULT-1001',
            'paid_at' => now(),
        ]);

        $response = $this->getJson('/api/storefront/payments/result?transaction_id=AUR-RESULT-1001');

        $response->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-RESULT-1001')
            ->assertJsonPath('data.payment_status', PaymentStatus::Paid->value)
            ->assertJsonPath('data.payment_method', PaymentMethod::SslCommerz->value)
            ->assertJsonPath('data.grand_total', 184000)
            ->assertJsonPath('data.payments.0.gateway_transaction_id', 'AUR-RESULT-1001');
    }

    public function test_payment_result_api_rejects_unknown_transaction_id(): void
    {
        $response = $this->getJson('/api/storefront/payments/result?transaction_id=missing-transaction');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_sslcommerz_fail_callback_redirects_browser_to_result_page(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::SslCommerz,
            'payment_status' => PaymentStatus::Pending,
        ]);
        Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'AUR-RESULT-FAIL',
        ]);

        $response = $this->post('/api/storefront/payments/sslcommerz/fail', [
            'tran_id' => 'AUR-RESULT-FAIL',
        ]);

        $response->assertRedirect('/payment/result?transaction_id=AUR-RESULT-FAIL&status=failed&event=failed');
    }
}
