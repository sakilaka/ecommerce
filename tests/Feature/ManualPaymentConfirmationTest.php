<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_bkash_payment_proof_for_matching_order(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-MANUAL-1001',
            'customer_phone' => '01712345678',
            'payment_method' => PaymentMethod::Bkash,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Pending,
            'amount' => 125000,
            'gateway' => 'bkash',
            'gateway_payload' => [
                'next_action' => 'manual_payment_instructions',
                'merchant_number' => '01700000000',
                'reference' => 'ORD-MANUAL-1001',
                'amount' => 125000,
            ],
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/manual-confirmation', [
            'order_number' => 'ORD-MANUAL-1001',
            'customer_phone' => '01712345678',
            'sender_phone' => '01812345678',
            'transaction_id' => 'BKASH-TXN-7788',
            'method' => 'bkash',
            'note' => 'Paid from personal bKash.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.gateway_transaction_id', 'BKASH-TXN-7788')
            ->assertJsonPath('data.gateway_payload.manual_submission.sender_phone', '01812345678')
            ->assertJsonPath('data.gateway_payload.manual_submission.status', 'submitted_for_review')
            ->assertJsonPath('data.status', PaymentStatus::Pending->value);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gateway_transaction_id' => 'BKASH-TXN-7788',
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payment.manual-proof.submitted',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);
        $this->assertSame('BKASH-TXN-7788', AuditLog::query()->where('event', 'payment.manual-proof.submitted')->first()?->new_values['transaction_id']);
    }

    public function test_manual_payment_proof_requires_matching_customer_order_details(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-MANUAL-1002',
            'customer_phone' => '01712345678',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Nagad,
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/manual-confirmation', [
            'order_number' => 'ORD-WRONG',
            'customer_phone' => '01712345678',
            'sender_phone' => '01812345678',
            'transaction_id' => 'NAGAD-TXN-9',
            'method' => 'nagad',
        ]);

        $response->assertNotFound();
        $this->assertNull($payment->refresh()->gateway_transaction_id);
    }

    public function test_manual_payment_proof_rejects_non_mobile_payment_method(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-MANUAL-1003',
            'customer_phone' => '01712345678',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->postJson('/api/storefront/payments/'.$payment->id.'/manual-confirmation', [
            'order_number' => 'ORD-MANUAL-1003',
            'customer_phone' => '01712345678',
            'sender_phone' => '01812345678',
            'transaction_id' => 'TXN-123456',
            'method' => 'bkash',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['method']);
    }

    public function test_admin_payment_summary_exposes_manual_mobile_submission(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['order_number' => 'ORD-MANUAL-1004']);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Pending,
            'gateway_transaction_id' => 'BKASH-VISIBLE-1',
            'gateway_payload' => [
                'manual_submission' => [
                    'sender_phone' => '01812345678',
                    'transaction_id' => 'BKASH-VISIBLE-1',
                    'status' => 'submitted_for_review',
                    'submitted_at' => '2026-09-04T09:00:00.000000Z',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/payments/'.$payment->id);

        $response->assertOk()
            ->assertJsonPath('data.gateway_summary.manual_transaction_id', 'BKASH-VISIBLE-1')
            ->assertJsonPath('data.gateway_summary.manual_sender_phone', '01812345678')
            ->assertJsonPath('data.gateway_summary.manual_status', 'submitted_for_review');
    }
}
