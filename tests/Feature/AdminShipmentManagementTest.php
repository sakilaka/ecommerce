<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_shipments(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['order_number' => 'ORD-260905-SHIP1111']);
        OrderItem::factory()->for($order)->create();
        $shipment = Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::Pending,
            'courier_name' => 'Steadfast',
            'recipient_name' => 'Sakil Ahmed',
            'recipient_phone' => '01712345678',
            'recipient_district' => 'Dhaka',
            'cod_amount' => 158000,
        ]);
        Shipment::factory()->create([
            'status' => ShipmentStatus::Delivered,
            'courier_name' => 'Pathao Courier',
            'recipient_district' => 'Chattogram',
            'cod_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/shipments?q=SHIP1111&status=pending&courier=Steadfast&district=Dhaka&cod_only=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id)
            ->assertJsonPath('data.0.status', ShipmentStatus::Pending->value)
            ->assertJsonPath('data.0.courier_name', 'Steadfast')
            ->assertJsonPath('data.0.order.order_number', 'ORD-260905-SHIP1111')
            ->assertJsonPath('data.0.order.items_count', 1);
    }

    public function test_admin_can_update_courier_tracking_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'confirmed_at' => null,
            'shipped_at' => null,
        ]);
        $shipment = Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::Pending,
            'courier_name' => null,
            'tracking_number' => null,
            'recipient_district' => 'Dhaka',
            'shipped_at' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/shipments/'.$shipment->id, [
            'status' => ShipmentStatus::Shipped->value,
            'courier_name' => 'Pathao Courier',
            'tracking_number' => 'PTH-123456',
            'tracking_url' => 'https://example.com/track/PTH-123456',
            'estimated_delivery_at' => now()->addDays(2)->toISOString(),
            'admin_note' => 'Packed and handed over to courier.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ShipmentStatus::Shipped->value)
            ->assertJsonPath('data.courier_name', 'Pathao Courier')
            ->assertJsonPath('data.tracking_number', 'PTH-123456')
            ->assertJsonPath('data.order.status', OrderStatus::Shipped->value)
            ->assertJsonPath('data.events.0.status', ShipmentStatus::Shipped->value)
            ->assertJsonPath('data.events.0.location', 'Dhaka');

        $shipment->refresh();
        $order->refresh();
        $this->assertNotNull($shipment->shipped_at);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->shipped_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'shipment.updated', 'auditable_type' => Shipment::class, 'auditable_id' => $shipment->id]);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'user_id' => $admin->id,
            'status' => ShipmentStatus::Shipped->value,
            'event_type' => 'status_update',
            'message' => 'Packed and handed over to courier.',
        ]);
    }

    public function test_failed_delivery_requires_reason_and_records_failed_event(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit, 'failed_at' => null]);

        $this->actingAs($admin)->patchJson('/api/admin/shipments/'.$shipment->id, [
            'status' => ShipmentStatus::Failed->value,
        ])->assertUnprocessable()->assertJsonValidationErrors(['admin_note']);

        $response = $this->actingAs($admin)->patchJson('/api/admin/shipments/'.$shipment->id, [
            'status' => ShipmentStatus::Failed->value,
            'admin_note' => 'Customer was unreachable after two delivery attempts.',
            'event_location' => 'Mirpur DOHS',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ShipmentStatus::Failed->value)
            ->assertJsonPath('data.events.0.event_type', 'delivery_failed')
            ->assertJsonPath('data.events.0.location', 'Mirpur DOHS');

        $shipment->refresh();
        $this->assertNotNull($shipment->failed_at);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::Failed->value,
            'event_type' => 'delivery_failed',
            'message' => 'Customer was unreachable after two delivery attempts.',
            'location' => 'Mirpur DOHS',
        ]);
    }

    public function test_admin_can_show_shipment_event_history(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::InTransit]);
        ShipmentEvent::factory()->for($shipment)->for($admin, 'user')->create([
            'status' => ShipmentStatus::InTransit,
            'event_type' => 'courier_scan',
            'location' => 'Dhaka Hub',
            'message' => 'Shipment scanned at hub.',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/shipments/'.$shipment->id);

        $response->assertOk()
            ->assertJsonPath('data.events.0.status', ShipmentStatus::InTransit->value)
            ->assertJsonPath('data.events.0.event_type', 'courier_scan')
            ->assertJsonPath('data.events.0.user.id', $admin->id);
    }

    public function test_shipment_update_requires_valid_status_and_tracking_url(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/shipments/'.$shipment->id, [
            'status' => 'packed',
            'tracking_url' => 'not-a-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'tracking_url']);
    }

    public function test_customer_cannot_manage_shipments(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $shipment = Shipment::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/shipments')->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/shipments/'.$shipment->id, [
            'status' => ShipmentStatus::Delivered->value,
        ])->assertForbidden();
    }
}
