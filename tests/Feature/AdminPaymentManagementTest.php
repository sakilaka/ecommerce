<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'order_number' => 'ORD-PAY-1001',
            'customer_name' => 'Sakil Ahmed',
            'customer_phone' => '01911111111',
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Paid,
            'amount' => 125000,
            'gateway' => 'bkash',
            'gateway_transaction_id' => 'TRX-BKASH-1',
            'paid_at' => now(),
        ]);
        Payment::factory()->create(['method' => PaymentMethod::CashOnDelivery, 'status' => PaymentStatus::Pending]);

        $response = $this->actingAs($admin)->getJson('/api/admin/payments?q=sakil&status=paid&method=bkash&gateway=bkash');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $payment->id)
            ->assertJsonPath('data.0.method', 'bkash')
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.order.order_number', 'ORD-PAY-1001')
            ->assertJsonPath('data.0.gateway_summary.bank_transaction_id', null)
            ->assertJsonPath('data.0.actions.can_refund', true)
            ->assertJsonStructure(['data' => [['id', 'method', 'status', 'amount', 'gateway', 'gateway_transaction_id', 'result_url', 'gateway_summary', 'actions', 'order']], 'links', 'meta']);
    }

    public function test_admin_can_export_filtered_payments_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $matchedOrder = Order::factory()->create([
            'order_number' => 'ORD-EXPORT-1001',
            'customer_name' => '=Export Customer',
            'customer_phone' => '01711112222',
            'customer_email' => 'export@example.test',
        ]);
        $payment = Payment::factory()->for($matchedOrder)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Paid,
            'amount' => 150000,
            'gateway' => 'bkash',
            'gateway_transaction_id' => 'TRX-EXPORT-1',
            'paid_at' => now(),
        ]);
        PaymentRefund::factory()->for($payment)->create(['amount' => 25000]);
        Payment::factory()->create(['method' => PaymentMethod::CashOnDelivery, 'status' => PaymentStatus::Pending]);

        $response = $this->actingAs($admin)->get('/api/admin/payments/export?status=paid&method=bkash&q=export');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $lines = array_values(array_filter(explode('
', trim($response->streamedContent()))));
        $header = str_getcsv($lines[0]);
        $row = str_getcsv($lines[1]);

        $this->assertSame('Payment ID', $header[0]);
        $this->assertSame('Order Number', $header[1]);
        $this->assertSame('ORD-EXPORT-1001', $row[1]);
        $this->assertSame("'=".'Export Customer', $row[2]);
        $this->assertSame('TRX-EXPORT-1', $row[8]);
        $this->assertSame('150000', $row[9]);
        $this->assertSame('25000', $row[10]);
        $this->assertSame('125000', $row[11]);
        $this->assertCount(2, $lines);
    }

    public function test_admin_can_show_payment_details(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['order_number' => 'ORD-PAY-SHOW']);
        $payment = Payment::factory()->for($order)->create([
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'TXN-SHOW-1',
            'gateway_payload' => [
                'sslcommerz' => ['sessionkey' => 'session-1', 'redirect_url' => 'https://sandbox.sslcommerz.com/pay/session-1'],
                'sslcommerz_callback' => [
                    'event' => 'success_validated',
                    'received_at' => '2026-09-04T10:00:00.000000Z',
                    'validation' => ['status' => 'VALID', 'bank_tran_id' => 'BANK-1'],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/payments/'.$payment->id);

        $response->assertOk()
            ->assertJsonPath('data.gateway_transaction_id', 'TXN-SHOW-1')
            ->assertJsonPath('data.result_url', url('/payment/result?transaction_id=TXN-SHOW-1'))
            ->assertJsonPath('data.gateway_summary.sessionkey', 'session-1')
            ->assertJsonPath('data.gateway_summary.callback_event', 'success_validated')
            ->assertJsonPath('data.gateway_summary.validation_status', 'VALID')
            ->assertJsonPath('data.gateway_summary.bank_transaction_id', 'BANK-1')
            ->assertJsonPath('data.order.order_number', 'ORD-PAY-SHOW');
    }

    public function test_admin_payment_details_include_audit_timeline(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Finance Admin']);
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Paid,
            'amount' => 200000,
            'created_at' => now()->subMinutes(3),
        ]);
        $refund = PaymentRefund::factory()->for($payment)->create([
            'amount' => 50000,
            'reference' => 'RF-1001',
        ]);

        AuditLog::factory()->create([
            'user_id' => $admin->id,
            'event' => 'payment.updated',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'paid', 'amount' => 200000],
            'created_at' => now()->subMinutes(2),
        ]);
        AuditLog::factory()->create([
            'user_id' => $admin->id,
            'event' => 'payment.refund.recorded',
            'auditable_type' => PaymentRefund::class,
            'auditable_id' => $refund->id,
            'new_values' => ['amount' => 50000, 'reference' => 'RF-1001'],
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/payments/'.$payment->id);

        $response->assertOk()
            ->assertJsonPath('data.timeline.0.type', 'payment.refund.recorded')
            ->assertJsonPath('data.timeline.0.label', 'Refund recorded')
            ->assertJsonPath('data.timeline.0.actor.name', 'Finance Admin')
            ->assertJsonPath('data.timeline.1.type', 'payment.updated')
            ->assertJsonPath('data.timeline.1.status', 'paid')
            ->assertJsonPath('data.timeline.2.type', 'payment.created');
    }

    public function test_admin_can_reconcile_payment_and_sync_order_payment_state(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::CashOnDelivery,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::CashOnDelivery,
            'status' => PaymentStatus::Pending,
            'amount' => 90000,
            'paid_at' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/payments/'.$payment->id, [
            'status' => 'paid',
            'method' => 'nagad',
            'amount' => 95000,
            'gateway' => 'nagad',
            'gateway_transaction_id' => 'NAGAD-TRX-22',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.method', 'nagad')
            ->assertJsonPath('data.amount', 95000)
            ->assertJsonPath('data.order.payment_status', 'paid')
            ->assertJsonPath('data.order.payment_method', 'nagad');

        $payment->refresh();
        $order->refresh();
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(PaymentMethod::Nagad, $order->payment_method);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'payment.updated',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);
        $this->assertSame('paid', AuditLog::query()->where('event', 'payment.updated')->first()?->new_values['status']);
    }

    public function test_payment_update_rejects_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/payments/'.$payment->id, [
            'status' => 'captured',
            'method' => 'rocket',
            'amount' => -1,
            'gateway_transaction_id' => str_repeat('x', 121),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'method', 'amount', 'gateway_transaction_id']);
    }

    public function test_admin_can_record_a_full_payment_refund_and_sync_order_payment_state(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => PaymentMethod::SslCommerz,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::SslCommerz,
            'status' => PaymentStatus::Paid,
            'amount' => 150000,
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => 'SSL-REFUND-1',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/payments/'.$payment->id.'/refunds', [
            'amount' => 150000,
            'method' => 'manual',
            'reference' => 'REF-1001',
            'reason' => 'Customer returned the full order.',
            'admin_note' => 'Approved by support.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.order.payment_status', 'refunded')
            ->assertJsonPath('data.refund_summary.refunded_amount', 150000)
            ->assertJsonPath('data.refund_summary.remaining_amount', 0)
            ->assertJsonPath('data.refunds.0.reference', 'REF-1001');

        $this->assertDatabaseHas('payment_refunds', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'amount' => 150000,
            'reference' => 'REF-1001',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'payment.refund.recorded',
            'auditable_type' => PaymentRefund::class,
        ]);
        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $order->refresh()->payment_status);
    }

    public function test_payment_refund_rejects_invalid_or_excess_amounts(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Paid,
            'amount' => 100000,
            'paid_at' => now(),
        ]);
        PaymentRefund::factory()->for($payment)->for($payment->order)->create([
            'amount' => 90000,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/payments/'.$payment->id.'/refunds', [
            'amount' => 20000,
            'method' => 'manual',
            'reason' => 'Trying to refund too much.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseMissing('payment_refunds', [
            'payment_id' => $payment->id,
            'amount' => 20000,
        ]);
    }

    public function test_only_paid_payments_can_be_refunded(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending,
            'amount' => 100000,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/payments/'.$payment->id.'/refunds', [
            'amount' => 50000,
            'method' => 'manual',
            'reason' => 'Refund before capture.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment']);
    }

    public function test_admin_can_approve_submitted_manual_payment_proof(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Bkash,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Pending,
            'gateway_transaction_id' => 'BKASH-APPROVE-1',
            'gateway_payload' => [
                'manual_submission' => [
                    'method' => 'bkash',
                    'sender_phone' => '01812345678',
                    'transaction_id' => 'BKASH-APPROVE-1',
                    'status' => 'submitted_for_review',
                    'submitted_at' => '2026-09-04T09:00:00.000000Z',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/payments/'.$payment->id.'/manual-proof', [
            'decision' => 'approved',
            'admin_note' => 'Matched bKash statement.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.order.payment_status', 'paid')
            ->assertJsonPath('data.gateway_summary.manual_status', 'verified')
            ->assertJsonPath('data.gateway_summary.manual_transaction_id', 'BKASH-APPROVE-1');

        $payment->refresh();
        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('verified', $payment->gateway_payload['manual_submission']['status']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'payment.manual-proof.approved',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_admin_can_reject_submitted_manual_payment_proof_without_marking_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Nagad,
        ]);
        $payment = Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Nagad,
            'status' => PaymentStatus::Pending,
            'gateway_transaction_id' => 'NAGAD-REJECT-1',
            'gateway_payload' => [
                'manual_submission' => [
                    'method' => 'nagad',
                    'sender_phone' => '01812345678',
                    'transaction_id' => 'NAGAD-REJECT-1',
                    'status' => 'submitted_for_review',
                    'submitted_at' => '2026-09-04T09:00:00.000000Z',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/payments/'.$payment->id.'/manual-proof', [
            'decision' => 'rejected',
            'admin_note' => 'Amount not found in statement.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order.payment_status', 'pending')
            ->assertJsonPath('data.gateway_summary.manual_status', 'rejected');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNull($payment->paid_at);
        $this->assertSame('rejected', $payment->gateway_payload['manual_submission']['status']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'payment.manual-proof.rejected',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_manual_payment_proof_review_requires_submitted_proof(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Pending,
            'gateway_payload' => ['manual_submission' => ['status' => 'rejected']],
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/payments/'.$payment->id.'/manual-proof', [
            'decision' => 'approved',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment']);
    }

    public function test_customer_cannot_manage_admin_payments(): void
    {
        $customer = User::factory()->create();
        $payment = Payment::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/payments')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/admin/payments/'.$payment->id)->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/payments/'.$payment->id, [
            'status' => 'paid',
            'method' => 'cod',
            'amount' => 1000,
        ])->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/payments/'.$payment->id.'/refunds', [
            'amount' => 1000,
            'method' => 'manual',
            'reason' => 'Unauthorized refund attempt.',
        ])->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/payments/'.$payment->id.'/manual-proof', [
            'decision' => 'approved',
        ])->assertForbidden();
    }
}
