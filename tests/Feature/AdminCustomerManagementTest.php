<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_customers(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create([
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.com',
            'phone' => '01911111111',
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);
        User::factory()->create(['name' => 'Hidden Admin', 'role' => UserRole::Admin]);
        User::factory()->create(['name' => 'Inactive Customer', 'role' => UserRole::Customer, 'is_active' => false]);
        CustomerAddress::factory()->for($customer)->create(['district' => 'Dhaka']);
        Order::factory()->for($customer)->create([
            'status' => OrderStatus::Delivered,
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 250000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/customers?q=nusrat&active=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nusrat Jahan')
            ->assertJsonPath('data.0.orders_count', 1)
            ->assertJsonPath('data.0.addresses_count', 1)
            ->assertJsonPath('data.0.delivered_orders_count', 1)
            ->assertJsonPath('data.0.paid_total', 250000)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'phone', 'is_active', 'orders_count']], 'links', 'meta']);
    }

    public function test_admin_can_show_customer_details(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['name' => 'Sakil Ahmed', 'role' => UserRole::Customer]);
        CustomerAddress::factory()->for($customer)->create(['district' => 'Chattogram', 'is_default' => true]);
        Order::factory()->for($customer)->create(['order_number' => 'ORD-CUSTOMER-001', 'shipping_district' => 'Chattogram']);

        $response = $this->actingAs($admin)->getJson('/api/admin/customers/'.$customer->id);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Sakil Ahmed')
            ->assertJsonPath('data.addresses.0.district', 'Chattogram')
            ->assertJsonPath('data.orders.0.order_number', 'ORD-CUSTOMER-001');
    }

    public function test_admin_can_update_customer_account_status(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer, 'is_active' => true]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/customers/'.$customer->id, [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_active' => false]);
    }

    public function test_admin_customer_routes_only_accept_customer_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/admin/customers/'.$otherAdmin->id)->assertNotFound();
        $this->actingAs($admin)->patchJson('/api/admin/customers/'.$otherAdmin->id, ['is_active' => false])->assertNotFound();
    }

    public function test_customer_cannot_manage_customers(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $otherCustomer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->getJson('/api/admin/customers')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/admin/customers/'.$otherCustomer->id)->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/customers/'.$otherCustomer->id, ['is_active' => false])->assertForbidden();
    }
}
