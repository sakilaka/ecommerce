<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'order_number' => 'ORD-260830-AAAA1111',
            'customer_name' => 'Sakil Ahmed',
            'customer_phone' => '01911111111',
            'shipping_district' => 'Dhaka',
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => PaymentMethod::CashOnDelivery,
        ]);
        Order::factory()->create(['order_number' => 'ORD-260830-BBBB2222', 'status' => OrderStatus::Delivered]);
        OrderItem::factory()->for($order)->create(['quantity' => 2]);

        $response = $this->actingAs($admin)->getJson('/api/admin/orders?q=sakil&status=pending&payment_method=cod');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-260830-AAAA1111')
            ->assertJsonPath('data.0.items_count', 1)
            ->assertJsonPath('data.0.shipping_district', 'Dhaka')
            ->assertJsonStructure(['data' => [['id', 'order_number', 'status', 'payment_status', 'grand_total']], 'links', 'meta']);
    }

    public function test_admin_can_show_order_details(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['customer_name' => 'Nusrat Jahan']);
        OrderItem::factory()->for($order)->create(['product_name' => 'Premium Saree', 'line_total' => 250000]);

        $response = $this->actingAs($admin)->getJson('/api/admin/orders/'.$order->id);

        $response->assertOk()
            ->assertJsonPath('data.customer_name', 'Nusrat Jahan')
            ->assertJsonPath('data.items.0.product_name', 'Premium Saree')
            ->assertJsonPath('data.items.0.line_total', 250000);
    }

    public function test_admin_can_update_order_status_and_payment_state(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'confirmed_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/orders/'.$order->id, [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'bkash',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_method', 'bkash');

        $order->refresh();
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->delivered_at);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'bkash']);
    }

    public function test_order_update_requires_valid_status_values(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/orders/'.$order->id, [
            'status' => 'packed',
            'payment_status' => 'waiting',
            'payment_method' => 'rocket',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'payment_status', 'payment_method']);
    }

    public function test_customer_cannot_manage_admin_orders(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/orders')->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/orders/'.$order->id, [
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ])->assertForbidden();
    }
}
