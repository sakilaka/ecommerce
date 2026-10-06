<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFulfillmentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_fulfillment_report(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['order_number' => 'ORD-FULFILL-1']);
        Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::Delivered,
            'courier_name' => 'Pathao Courier',
            'recipient_district' => 'Dhaka',
            'cod_amount' => 150000,
            'delivered_at' => now(),
            'created_at' => now(),
        ]);
        Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::InTransit,
            'courier_name' => 'Pathao Courier',
            'recipient_district' => 'Dhaka',
            'cod_amount' => 220000,
            'estimated_delivery_at' => now()->subDay(),
            'created_at' => now(),
        ]);
        Shipment::factory()->for($order)->create([
            'status' => ShipmentStatus::Failed,
            'courier_name' => 'RedX',
            'recipient_district' => 'Chattogram',
            'cod_amount' => 99000,
            'created_at' => now(),
        ]);
        Shipment::factory()->for($order)->create([
            'direction' => 'return_pickup',
            'status' => ShipmentStatus::Pending,
            'courier_name' => 'Steadfast',
            'recipient_district' => 'Dhaka',
            'cod_amount' => 0,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/fulfillment-reports?period=7');

        $response->assertOk()
            ->assertJsonPath('data.period.days', 7)
            ->assertJsonPath('data.summary.shipments', 4)
            ->assertJsonPath('data.summary.open_shipments', 3)
            ->assertJsonPath('data.summary.delivered_shipments', 1)
            ->assertJsonPath('data.summary.failed_shipments', 1)
            ->assertJsonPath('data.summary.delayed_shipments', 1)
            ->assertJsonPath('data.summary.return_pickups', 1)
            ->assertJsonPath('data.summary.cod_pending', 319000)
            ->assertJsonPath('data.summary.delivery_rate_percent', 25)
            ->assertJsonPath('data.courier_performance.0.courier', 'Pathao Courier')
            ->assertJsonPath('data.district_load.0.district', 'Dhaka')
            ->assertJsonPath('data.attention_queue.0.reason', 'failed_delivery')
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'daily_fulfillment' => [['date', 'created', 'delivered']],
                    'courier_performance' => [['courier', 'total', 'delivered', 'failed', 'delayed', 'cod_amount']],
                    'district_load' => [['district', 'total', 'open', 'delayed']],
                    'attention_queue' => [['id', 'order_number', 'direction', 'status', 'recipient_phone', 'reason']],
                ],
            ]);
    }

    public function test_customer_cannot_view_fulfillment_report(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/fulfillment-reports')->assertForbidden();
    }

    public function test_fulfillment_report_rejects_invalid_period(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/admin/fulfillment-reports?period=365')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }
}
