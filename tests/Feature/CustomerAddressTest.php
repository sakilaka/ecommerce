<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_default_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/customer/addresses', [
            'label' => 'Home',
            'name' => 'Sakil Ahmed',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'area' => 'Mirpur',
            'postal_code' => '1216',
            'address_line' => 'House 12, Road 4',
            'is_default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $user->id,
            'phone' => '01712345678',
            'is_default' => true,
        ]);
    }

    public function test_customer_can_have_only_one_default_address(): void
    {
        $user = User::factory()->create();
        $oldAddress = CustomerAddress::factory()->for($user)->create(['is_default' => true]);
        $newAddress = CustomerAddress::factory()->for($user)->create(['is_default' => false]);

        $response = $this->actingAs($user)->patchJson('/api/customer/addresses/'.$newAddress->id, [
            'label' => 'Office',
            'name' => 'Sakil Ahmed',
            'phone' => '01812345678',
            'district' => 'Dhaka',
            'area' => 'Banani',
            'postal_code' => '1213',
            'address_line' => 'Tower 2, Level 8',
            'is_default' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('customer_addresses', ['id' => $newAddress->id, 'is_default' => true]);
        $this->assertDatabaseHas('customer_addresses', ['id' => $oldAddress->id, 'is_default' => false]);
    }

    public function test_customer_session_routes_can_create_and_delete_address(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->postJson('/customer/addresses', [
            'label' => 'Home',
            'name' => 'Sakil Ahmed',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'area' => 'Mirpur',
            'postal_code' => '1216',
            'address_line' => 'House 12, Road 4',
            'is_default' => true,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.label', 'Home');

        $address = CustomerAddress::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->deleteJson('/customer/addresses/'.$address->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    public function test_customer_cannot_update_another_customers_address(): void
    {
        $user = User::factory()->create();
        $address = CustomerAddress::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/customer/addresses/'.$address->id, [
            'label' => 'Office',
            'name' => 'Sakil Ahmed',
            'phone' => '01812345678',
            'district' => 'Dhaka',
            'area' => 'Banani',
            'postal_code' => '1213',
            'address_line' => 'Tower 2, Level 8',
            'is_default' => true,
        ]);

        $response->assertNotFound();
    }
}
