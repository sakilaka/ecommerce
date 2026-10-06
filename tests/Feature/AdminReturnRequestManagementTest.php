<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReturnRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_return_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['name' => 'Nusrat Jahan', 'phone' => '01911111111']);
        $order = Order::factory()->for($customer)->create([
            'order_number' => 'ORD-RETURN-1001',
            'customer_name' => 'Nusrat Jahan',
            'customer_phone' => '01911111111',
            'status' => OrderStatus::Delivered,
        ]);
        $item = OrderItem::factory()->for($order)->create(['product_name' => 'Premium Saree']);
        $returnRequest = ReturnRequest::factory()->for($order)->for($item, 'orderItem')->for($customer, 'user')->create([
            'return_number' => 'RET-1001',
            'status' => ReturnStatus::Requested,
            'reason' => 'Wrong size delivered.',
        ]);
        ReturnRequest::factory()->create(['return_number' => 'RET-OTHER', 'status' => ReturnStatus::Refunded]);

        $response = $this->actingAs($admin)->getJson('/api/admin/return-requests?q=nusrat&status=requested');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $returnRequest->id)
            ->assertJsonPath('data.0.return_number', 'RET-1001')
            ->assertJsonPath('data.0.status', 'requested')
            ->assertJsonPath('data.0.order.order_number', 'ORD-RETURN-1001')
            ->assertJsonPath('data.0.order_item.product_name', 'Premium Saree')
            ->assertJsonStructure(['data' => [['id', 'return_number', 'status', 'refund_amount', 'reason', 'order', 'order_item']], 'links', 'meta']);
    }

    public function test_admin_can_show_return_request_details(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['order_number' => 'ORD-DETAIL-1']);
        $item = OrderItem::factory()->for($order)->create(['sku' => 'SKU-RET-1']);
        $returnRequest = ReturnRequest::factory()->for($order)->for($item, 'orderItem')->create(['return_number' => 'RET-DETAIL']);

        $response = $this->actingAs($admin)->getJson('/api/admin/return-requests/'.$returnRequest->id);

        $response->assertOk()
            ->assertJsonPath('data.return_number', 'RET-DETAIL')
            ->assertJsonPath('data.order.order_number', 'ORD-DETAIL-1')
            ->assertJsonPath('data.order_item.sku', 'SKU-RET-1');
    }

    public function test_admin_can_update_return_status_and_refund_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $returnRequest = ReturnRequest::factory()->create([
            'status' => ReturnStatus::Requested,
            'refund_amount' => 0,
            'approved_at' => null,
            'refunded_at' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/return-requests/'.$returnRequest->id, [
            'status' => 'refunded',
            'refund_amount' => 125000,
            'admin_note' => 'Refund processed after warehouse check.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.refund_amount', 125000)
            ->assertJsonPath('data.admin_note', 'Refund processed after warehouse check.');

        $returnRequest->refresh();
        $this->assertNotNull($returnRequest->refunded_at);
        $this->assertDatabaseHas('return_requests', [
            'id' => $returnRequest->id,
            'status' => 'refunded',
            'refund_amount' => 125000,
            'admin_note' => 'Refund processed after warehouse check.',
        ]);
    }

    public function test_admin_update_schedules_and_completes_return_pickup(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->create([
            'customer_name' => 'Nusrat Jahan',
            'customer_phone' => '01911111111',
            'shipping_address' => ['address_line' => 'House 12, Dhanmondi'],
            'shipping_district' => 'Dhaka',
            'shipping_area' => 'Dhanmondi',
            'status' => OrderStatus::Delivered,
        ]);
        $item = OrderItem::factory()->for($order)->create(['product_name' => 'Premium Saree']);
        $returnRequest = ReturnRequest::factory()->for($order)->for($item, 'orderItem')->for($customer, 'user')->create([
            'return_number' => 'RET-PICKUP-1',
            'status' => ReturnStatus::Requested,
            'refund_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/return-requests/'.$returnRequest->id, [
            'status' => ReturnStatus::Approved->value,
            'refund_amount' => 125000,
            'admin_note' => 'Return approved after support review.',
            'pickup_courier_name' => 'Pathao Courier',
            'pickup_tracking_number' => 'RET-PTH-1001',
            'pickup_tracking_url' => 'https://example.com/track/RET-PTH-1001',
            'pickup_note' => 'Pickup scheduled from customer address.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ReturnStatus::Approved->value)
            ->assertJsonPath('data.pickup_shipment.direction', 'return_pickup')
            ->assertJsonPath('data.pickup_shipment.status', ShipmentStatus::Pending->value)
            ->assertJsonPath('data.pickup_shipment.courier_name', 'Pathao Courier')
            ->assertJsonPath('data.pickup_shipment.tracking_number', 'RET-PTH-1001')
            ->assertJsonPath('data.pickup_shipment.events.0.event_type', 'return_pickup_created');

        $this->assertDatabaseHas('shipments', [
            'return_request_id' => $returnRequest->id,
            'order_id' => $order->id,
            'direction' => 'return_pickup',
            'status' => ShipmentStatus::Pending->value,
            'courier_name' => 'Pathao Courier',
            'tracking_number' => 'RET-PTH-1001',
            'recipient_phone' => '01911111111',
            'recipient_district' => 'Dhaka',
            'recipient_area' => 'Dhanmondi',
            'recipient_address' => 'House 12, Dhanmondi',
            'admin_note' => 'Pickup scheduled from customer address.',
        ]);
        $this->assertDatabaseHas('shipment_events', [
            'event_type' => 'return_pickup_created',
            'status' => ShipmentStatus::Pending->value,
            'message' => 'Pickup scheduled from customer address.',
            'is_public' => true,
        ]);

        $completeResponse = $this->actingAs($admin)->patchJson('/api/admin/return-requests/'.$returnRequest->id, [
            'status' => ReturnStatus::Received->value,
            'refund_amount' => 125000,
            'admin_note' => 'Warehouse received the returned item.',
            'pickup_courier_name' => 'Pathao Courier',
            'pickup_tracking_number' => 'RET-PTH-1001',
            'pickup_note' => 'Returned item received at warehouse.',
        ]);

        $completeResponse->assertOk()
            ->assertJsonPath('data.status', ReturnStatus::Received->value)
            ->assertJsonPath('data.pickup_shipment.status', ShipmentStatus::Delivered->value)
            ->assertJsonPath('data.pickup_shipment.events.0.event_type', 'return_received');

        $shipment = Shipment::query()->where('return_request_id', $returnRequest->id)->firstOrFail();
        $this->assertNotNull($shipment->delivered_at);
        $this->assertSame(2, $shipment->events()->count());
    }

    public function test_return_update_rejects_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();
        $returnRequest = ReturnRequest::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/return-requests/'.$returnRequest->id, [
            'status' => 'paid_back',
            'refund_amount' => -1,
            'admin_note' => str_repeat('x', 1001),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'refund_amount', 'admin_note']);
    }

    public function test_customer_cannot_manage_admin_return_requests(): void
    {
        $customer = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/return-requests')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/admin/return-requests/'.$returnRequest->id)->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/return-requests/'.$returnRequest->id, [
            'status' => 'approved',
            'refund_amount' => 1000,
            'admin_note' => null,
        ])->assertForbidden();
    }
}
