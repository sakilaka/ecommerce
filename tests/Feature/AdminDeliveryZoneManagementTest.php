<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeliveryZoneManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_delivery_zones(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'cod_charge' => 1000,
            'cod_available' => true,
            'is_active' => true,
        ]);
        DeliveryZone::factory()->create([
            'district' => 'Sylhet',
            'area' => null,
            'cod_available' => false,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/delivery-zones?q=dhaka&active=1&cod=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $zone->id)
            ->assertJsonPath('data.0.district', 'Dhaka')
            ->assertJsonPath('data.0.delivery_charge', 8000)
            ->assertJsonPath('data.0.cod_charge', 1000)
            ->assertJsonPath('data.0.cod_available', true)
            ->assertJsonStructure(['data' => [['id', 'district', 'area', 'delivery_charge', 'estimated_min_days', 'estimated_max_days', 'cod_available', 'is_active']], 'links', 'meta']);
    }

    public function test_admin_can_create_delivery_zone(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/delivery-zones', [
            'district' => 'Barishal',
            'area' => 'City',
            'delivery_charge' => 13000,
            'cod_charge' => 500,
            'estimated_min_days' => 2,
            'estimated_max_days' => 5,
            'cod_available' => true,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.district', 'Barishal')
            ->assertJsonPath('data.area', 'City')
            ->assertJsonPath('data.delivery_charge', 13000);

        $this->assertDatabaseHas('delivery_zones', [
            'district' => 'Barishal',
            'area' => 'City',
            'delivery_charge' => 13000,
            'cod_charge' => 500,
        ]);
    }

    public function test_admin_can_update_delivery_zone(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = DeliveryZone::factory()->create([
            'district' => 'Khulna',
            'area' => null,
            'delivery_charge' => 14000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/delivery-zones/'.$zone->id, [
            'district' => 'Khulna',
            'area' => 'Sonadanga',
            'delivery_charge' => 15000,
            'cod_charge' => 1000,
            'estimated_min_days' => 3,
            'estimated_max_days' => 6,
            'cod_available' => false,
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.area', 'Sonadanga')
            ->assertJsonPath('data.cod_available', false)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('delivery_zones', [
            'id' => $zone->id,
            'area' => 'Sonadanga',
            'delivery_charge' => 15000,
            'cod_available' => false,
            'is_active' => false,
        ]);
    }

    public function test_delivery_zone_validation_rejects_unsafe_values(): void
    {
        $admin = User::factory()->admin()->create();
        DeliveryZone::factory()->create(['district' => 'Dhaka', 'area' => 'Mirpur']);

        $response = $this->actingAs($admin)->postJson('/api/admin/delivery-zones', [
            'district' => 'Dhaka',
            'area' => 'Mirpur',
            'delivery_charge' => -1,
            'cod_charge' => 0,
            'estimated_min_days' => 6,
            'estimated_max_days' => 2,
            'cod_available' => true,
            'is_active' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'area',
                'delivery_charge',
                'estimated_max_days',
            ]);
    }

    public function test_customer_cannot_manage_delivery_zones(): void
    {
        $customer = User::factory()->create();
        $zone = DeliveryZone::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/delivery-zones')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/delivery-zones', [])->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/delivery-zones/'.$zone->id, [])->assertForbidden();
    }
}
