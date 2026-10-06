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

class CustomerReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_return_for_delivered_refundable_item(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDay(),
        ]);
        $item = OrderItem::factory()->for($order)->create([
            'product_name' => 'Premium Panjabi',
            'is_refundable' => true,
            'return_window_days' => 7,
            'line_total' => 125000,
        ]);

        $response = $this->actingAs($user)->postJson('/api/customer/return-requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'reason' => 'The size does not fit properly.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', ReturnStatus::Requested->value)
            ->assertJsonPath('data.refund_amount', 125000)
            ->assertJsonPath('data.order_item.product_name', 'Premium Panjabi');

        $this->assertDatabaseHas('return_requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'user_id' => $user->id,
            'status' => ReturnStatus::Requested->value,
            'reason' => 'The size does not fit properly.',
        ]);
    }

    public function test_customer_can_list_their_return_requests(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $returnRequest = ReturnRequest::factory()->for($user, 'user')->create(['return_number' => 'RET-CUSTOMER-1']);
        ReturnRequest::factory()->for($otherUser, 'user')->create(['return_number' => 'RET-OTHER-1']);

        $response = $this->actingAs($user)->getJson('/api/customer/return-requests');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $returnRequest->id)
            ->assertJsonPath('data.0.return_number', 'RET-CUSTOMER-1');
    }

    public function test_customer_can_see_return_pickup_tracking(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
        $item = OrderItem::factory()->for($order)->create(['product_name' => 'Smart Watch']);
        $returnRequest = ReturnRequest::factory()->for($order)->for($item, 'orderItem')->for($user, 'user')->create([
            'return_number' => 'RET-CUSTOMER-PICKUP',
            'status' => ReturnStatus::Approved,
        ]);
        $shipment = Shipment::factory()->for($order)->for($returnRequest)->create([
            'direction' => 'return_pickup',
            'status' => ShipmentStatus::Pending,
            'courier_name' => 'Steadfast',
            'tracking_number' => 'RET-STF-55',
            'tracking_url' => 'https://example.com/track/RET-STF-55',
        ]);
        $shipment->events()->create([
            'status' => ShipmentStatus::Pending,
            'event_type' => 'return_pickup_created',
            'location' => 'Dhaka',
            'message' => 'Return pickup has been scheduled.',
            'is_public' => true,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/customer/return-requests');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.return_number', 'RET-CUSTOMER-PICKUP')
            ->assertJsonPath('data.0.pickup_shipment.direction', 'return_pickup')
            ->assertJsonPath('data.0.pickup_shipment.courier_name', 'Steadfast')
            ->assertJsonPath('data.0.pickup_shipment.tracking_number', 'RET-STF-55')
            ->assertJsonPath('data.0.pickup_shipment.events.0.event_type', 'return_pickup_created');
    }

    public function test_customer_cannot_request_return_before_delivery(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Processing]);
        $item = OrderItem::factory()->for($order)->create(['is_refundable' => true]);

        $response = $this->actingAs($user)->postJson('/api/customer/return-requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'reason' => 'I want to return this product.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Returns can only be requested after delivery.');
    }

    public function test_customer_cannot_request_return_for_non_refundable_item(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
        $item = OrderItem::factory()->for($order)->create(['is_refundable' => false]);

        $response = $this->actingAs($user)->postJson('/api/customer/return-requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'reason' => 'I want to return this product.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'This product is not refundable.');
    }

    public function test_customer_cannot_duplicate_open_return_for_same_item(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
        $item = OrderItem::factory()->for($order)->create(['is_refundable' => true]);
        ReturnRequest::factory()->for($order)->for($item, 'orderItem')->for($user, 'user')->create([
            'status' => ReturnStatus::Requested,
        ]);

        $response = $this->actingAs($user)->postJson('/api/customer/return-requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'reason' => 'I want to return this product.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'A return request already exists for this item.');
    }

    public function test_customer_cannot_return_another_customers_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($otherUser)->create(['status' => OrderStatus::Delivered]);
        $item = OrderItem::factory()->for($order)->create(['is_refundable' => true]);

        $response = $this->actingAs($user)->postJson('/api/customer/return-requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'reason' => 'I want to return this product.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');
    }
}
