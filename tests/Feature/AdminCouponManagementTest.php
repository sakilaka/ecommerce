<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_coupons(): void
    {
        $admin = User::factory()->admin()->create();
        $coupon = Coupon::factory()->create([
            'code' => 'EID50',
            'type' => DiscountType::Percent,
            'value' => 50,
            'minimum_order_amount' => 199900,
            'usage_limit' => 100,
            'used_count' => 2,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ]);
        Coupon::factory()->create(['code' => 'OLD10', 'is_active' => false]);
        Order::factory()->for($coupon)->create([
            'payment_status' => PaymentStatus::Paid,
            'discount_total' => 50000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/coupons?q=eid&status=running&type=percent');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'EID50')
            ->assertJsonPath('data.0.type', 'percent')
            ->assertJsonPath('data.0.is_running', true)
            ->assertJsonPath('data.0.orders_count', 1)
            ->assertJsonPath('data.0.orders_sum_discount_total', 50000)
            ->assertJsonStructure(['data' => [['id', 'code', 'type', 'value', 'minimum_order_amount', 'is_active']], 'links', 'meta']);
    }

    public function test_admin_can_create_coupon(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => 'boishakh20',
            'type' => DiscountType::Percent->value,
            'value' => 20,
            'minimum_order_amount' => 99900,
            'maximum_discount_amount' => 30000,
            'usage_limit' => 200,
            'used_count' => 0,
            'per_customer_limit' => 1,
            'starts_at' => now()->subHour()->toISOString(),
            'ends_at' => now()->addWeek()->toISOString(),
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'BOISHAKH20')
            ->assertJsonPath('data.value', 20)
            ->assertJsonPath('data.remaining_uses', 200);

        $this->assertDatabaseHas('coupons', ['code' => 'BOISHAKH20', 'type' => 'percent', 'value' => 20]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $admin = User::factory()->admin()->create();
        $coupon = Coupon::factory()->create(['code' => 'SAVE100', 'type' => DiscountType::Fixed, 'value' => 10000]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/coupons/'.$coupon->id, [
            'code' => 'SAVE150',
            'type' => DiscountType::Fixed->value,
            'value' => 15000,
            'minimum_order_amount' => 50000,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 3,
            'per_customer_limit' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'SAVE150')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.used_count', 3);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'code' => 'SAVE150', 'value' => 15000, 'is_active' => false]);
    }

    public function test_coupon_validation_rejects_unsafe_values(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => 'BAD500',
            'type' => DiscountType::Percent->value,
            'value' => 500,
            'minimum_order_amount' => 0,
            'starts_at' => now()->subWeek()->toISOString(),
            'ends_at' => now()->addWeek()->toISOString(),
            'is_active' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);

        $dateResponse = $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => 'BADDATE',
            'type' => DiscountType::Fixed->value,
            'value' => 5000,
            'minimum_order_amount' => 0,
            'starts_at' => now()->addWeek()->toISOString(),
            'ends_at' => now()->subWeek()->toISOString(),
            'is_active' => true,
        ]);

        $dateResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['ends_at']);
    }

    public function test_customer_cannot_manage_coupons(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $coupon = Coupon::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/coupons')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/coupons', [
            'code' => 'NOPE',
            'type' => DiscountType::Fixed->value,
            'value' => 1000,
            'minimum_order_amount' => 0,
            'is_active' => true,
        ])->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/coupons/'.$coupon->id, [
            'code' => 'NOPE2',
            'type' => DiscountType::Fixed->value,
            'value' => 1000,
            'minimum_order_amount' => 0,
            'is_active' => true,
        ])->assertForbidden();
    }
}
