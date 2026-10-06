<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_their_recent_orders(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['order_number' => 'ORD-260829-ABC12345']);
        OrderItem::factory()->for($order)->create(['product_name' => 'Premium Panjabi']);
        Payment::factory()->for($order)->create(['amount' => $order->grand_total]);
        Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::Shipped,
            'courier_name' => 'Steadfast',
            'tracking_number' => 'STD-1001',
            'recipient_district' => 'Dhaka',
        ]);
        Order::factory()->create(['order_number' => 'ORD-260829-OTHER999']);

        $response = $this->actingAs($user)->getJson('/api/customer/orders');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-260829-ABC12345')
            ->assertJsonPath('data.0.items.0.product_name', 'Premium Panjabi')
            ->assertJsonPath('data.0.shipments.0.status', ShipmentStatus::Shipped->value)
            ->assertJsonPath('data.0.shipments.0.courier_name', 'Steadfast')
            ->assertJsonPath('data.0.shipments.0.tracking_number', 'STD-1001');
    }

    public function test_customer_session_route_can_list_recent_orders(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['order_number' => 'ORD-260829-WEB12345']);
        OrderItem::factory()->for($order)->create(['product_name' => 'Premium Saree']);

        $response = $this->actingAs($user)->getJson('/customer/orders');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-260829-WEB12345')
            ->assertJsonPath('data.0.items.0.product_name', 'Premium Saree');
    }

    public function test_customer_order_list_includes_manual_payment_review_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['order_number' => 'ORD-260904-MANUAL01']);
        Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Bkash,
            'status' => PaymentStatus::Pending,
            'gateway_transaction_id' => 'BKASH-CUST-1',
            'gateway_payload' => [
                'manual_submission' => [
                    'sender_phone' => '01812345678',
                    'transaction_id' => 'BKASH-CUST-1',
                    'status' => 'submitted_for_review',
                    'submitted_at' => '2026-09-04T10:00:00.000000Z',
                    'admin_note' => 'Internal note should not be exposed.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/customer/orders');

        $response->assertOk()
            ->assertJsonPath('data.0.payments.0.manual_summary.status', 'submitted_for_review')
            ->assertJsonPath('data.0.payments.0.manual_summary.sender_phone', '01812345678')
            ->assertJsonMissingPath('data.0.payments.0.manual_summary.admin_note')
            ->assertJsonMissingPath('data.0.payments.0.gateway_payload.manual_submission.admin_note');
    }

    public function test_guest_order_tracking_includes_manual_payment_review_status(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-260904-MANUAL02',
            'customer_phone' => '01712345678',
            'payment_method' => PaymentMethod::Nagad,
            'payment_status' => PaymentStatus::Pending,
        ]);
        Payment::factory()->for($order)->create([
            'method' => PaymentMethod::Nagad,
            'status' => PaymentStatus::Pending,
            'gateway_transaction_id' => 'NAGAD-CUST-1',
            'gateway_payload' => [
                'manual_submission' => [
                    'sender_phone' => '01812345678',
                    'transaction_id' => 'NAGAD-CUST-1',
                    'status' => 'rejected',
                    'submitted_at' => '2026-09-04T10:00:00.000000Z',
                    'reviewed_at' => '2026-09-04T10:10:00.000000Z',
                    'admin_note' => 'Internal note should not be exposed.',
                ],
            ],
        ]);

        $response = $this->postJson('/api/storefront/orders/track', [
            'order_number' => 'ORD-260904-MANUAL02',
            'phone' => '01712345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payments.0.manual_summary.status', 'rejected')
            ->assertJsonPath('data.payments.0.manual_summary.transaction_id', 'NAGAD-CUST-1')
            ->assertJsonPath('data.payments.0.manual_summary.reviewed_at', '2026-09-04T10:10:00.000000Z')
            ->assertJsonMissingPath('data.payments.0.manual_summary.admin_note')
            ->assertJsonMissingPath('data.payments.0.gateway_payload.manual_submission.admin_note');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/customer/orders/'.$order->id);

        $response->assertNotFound();
    }

    public function test_guest_can_track_order_by_number_and_phone(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-260829-TRACK123',
            'customer_phone' => '01712345678',
            'status' => OrderStatus::Processing,
        ]);
        OrderItem::factory()->for($order)->create(['product_name' => 'Wireless Earbuds']);
        $shipment = Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::InTransit,
            'courier_name' => 'Pathao Courier',
            'tracking_number' => 'PTH-TRACK-123',
            'tracking_url' => 'https://example.com/track/PTH-TRACK-123',
            'cod_amount' => $order->grand_total,
        ]);
        ShipmentEvent::factory()->for($shipment)->create([
            'status' => ShipmentStatus::InTransit,
            'event_type' => 'courier_scan',
            'location' => 'Dhaka Hub',
            'message' => 'Shipment scanned at Dhaka Hub.',
            'is_public' => true,
        ]);
        ShipmentEvent::factory()->for($shipment)->create([
            'status' => ShipmentStatus::Failed,
            'event_type' => 'internal_note',
            'message' => 'Internal courier escalation.',
            'is_public' => false,
        ]);

        $response = $this->postJson('/api/storefront/orders/track', [
            'order_number' => 'ORD-260829-TRACK123',
            'phone' => '01712345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-260829-TRACK123')
            ->assertJsonPath('data.status', OrderStatus::Processing->value)
            ->assertJsonPath('data.items.0.product_name', 'Wireless Earbuds')
            ->assertJsonPath('data.shipments.0.status', ShipmentStatus::InTransit->value)
            ->assertJsonPath('data.shipments.0.courier_name', 'Pathao Courier')
            ->assertJsonPath('data.shipments.0.tracking_number', 'PTH-TRACK-123')
            ->assertJsonPath('data.shipments.0.cod_amount', $order->grand_total)
            ->assertJsonPath('data.shipments.0.events.0.event_type', 'courier_scan')
            ->assertJsonPath('data.shipments.0.events.0.location', 'Dhaka Hub')
            ->assertJsonMissingPath('data.shipments.0.events.1');
    }

    public function test_guest_order_tracking_requires_matching_phone(): void
    {
        Order::factory()->create([
            'order_number' => 'ORD-260829-TRACK123',
            'customer_phone' => '01712345678',
        ]);

        $response = $this->postJson('/api/storefront/orders/track', [
            'order_number' => 'ORD-260829-TRACK123',
            'phone' => '01812345678',
        ]);

        $response->assertNotFound();
    }
}
