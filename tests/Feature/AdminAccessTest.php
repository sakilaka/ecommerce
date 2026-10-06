<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_api_health(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->getJson('/api/admin/health');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_api_health(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/health');

        $response->assertOk()
            ->assertJsonPath('ok', true);
    }
}
