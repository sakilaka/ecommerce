<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '01712345678',
        ]);

        $response = $this->actingAs($user)->patchJson('/auth/profile', [
            'name' => 'Sakil Ahmed',
            'email' => 'sakil@example.com',
            'phone' => '01812345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Sakil Ahmed')
            ->assertJsonPath('data.email', 'sakil@example.com')
            ->assertJsonPath('data.phone', '01812345678');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Sakil Ahmed',
            'email' => 'sakil@example.com',
            'phone' => '01812345678',
        ]);
    }

    public function test_customer_profile_requires_unique_email_and_phone(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
            'phone' => '01812345678',
        ]);
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '01712345678',
        ]);

        $response = $this->actingAs($user)->patchJson('/auth/profile', [
            'name' => 'Buyer',
            'email' => 'taken@example.com',
            'phone' => '01812345678',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'buyer@example.com',
            'phone' => '01712345678',
        ]);
    }

    public function test_customer_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->patchJson('/auth/password', [
            'current_password' => 'password123',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password updated.');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_customer_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->patchJson('/auth/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }
}
