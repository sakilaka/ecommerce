<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_lists_only_active_delivery_zones(): void
    {
        $activeZone = DeliveryZone::factory()->create([
            'district' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'delivery_charge' => 8000,
            'is_active' => true,
        ]);
        DeliveryZone::factory()->create(['district' => 'Barishal', 'is_active' => false]);

        $response = $this->getJson('/api/storefront/delivery-zones');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeZone->id)
            ->assertJsonPath('data.0.delivery_charge', 8000);
    }
}
