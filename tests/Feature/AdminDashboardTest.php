<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login_page(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_can_render(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_admin_user_seeder_creates_default_admin_credentials(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@aurelia.test')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('Admin@12345', $admin->password));
    }

    public function test_admin_can_view_dashboard_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_customer_cannot_view_admin_dashboard_page(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_can_view_dashboard_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        $customers = User::factory()->count(2)->create();
        Order::factory()->for($customers[0])->create([
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 250000,
        ]);
        Order::factory()->for($customers[1])->create([
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Unpaid,
            'grand_total' => 175000,
        ]);
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create([
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.stats.orders_total', 2)
            ->assertJsonPath('data.stats.orders_pending', 1)
            ->assertJsonPath('data.stats.orders_processing', 1)
            ->assertJsonPath('data.stats.orders_month', 2)
            ->assertJsonPath('data.stats.revenue_total', 250000)
            ->assertJsonPath('data.stats.low_stock_variants', 1)
            ->assertJsonPath('data.stats.customers_total', 2)
            ->assertJsonPath('data.stats.customers_month', 2)
            ->assertJsonCount(2, 'data.recent_orders')
            ->assertJsonCount(1, 'data.stock_alerts')
            ->assertJsonCount(12, 'data.sales_trend')
            ->assertJsonCount(2, 'data.recent_buyers');

        $this->assertSame(2, $response->json('data.sales_trend.11.orders_count'));
        $this->assertSame(425000, $response->json('data.sales_trend.11.total'));
    }

    public function test_customer_cannot_view_admin_dashboard_metrics(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->getJson('/api/admin/dashboard');

        $response->assertForbidden();
    }
}
