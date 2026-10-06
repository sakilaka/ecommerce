<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_with_email_and_phone(): void
    {
        $response = $this->postJson('/auth/register', [
            'name' => 'Sakil Ahmed',
            'email' => 'sakil@example.com',
            'phone' => '01712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'sakil@example.com')
            ->assertJsonPath('data.phone', '01712345678')
            ->assertJsonPath('data.role', UserRole::Customer->value);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'sakil@example.com', 'phone' => '01712345678']);
    }

    public function test_customer_can_login_with_email_or_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '01812345678',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $emailResponse = $this->postJson('/auth/login', [
            'login' => 'buyer@example.com',
            'password' => 'password123',
        ]);

        $emailResponse->assertOk()
            ->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user);

        $this->postJson('/auth/logout')->assertOk();
        $this->assertGuest();

        $phoneResponse = $this->postJson('/auth/login', [
            'login' => '01812345678',
            'password' => 'password123',
        ]);

        $phoneResponse->assertOk()
            ->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_is_redirected_from_customer_dashboard_to_login(): void
    {
        $this->get('/customer/dashboard')->assertRedirect('/login');
    }

    public function test_guest_dashboard_redirect_keeps_requested_panel(): void
    {
        $this->get('/customer/dashboard?panel=wishlist')
            ->assertRedirect('/login?next='.rawurlencode('/customer/dashboard?panel=wishlist'));
    }

    public function test_customer_login_and_register_pages_render_for_guests(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_authenticated_customer_can_view_dashboard_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/customer/dashboard')->assertOk();
    }

    public function test_authenticated_customer_is_redirected_from_login_to_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/login')->assertRedirect('/customer/dashboard');
        $this->actingAs($customer)->get('/register')->assertRedirect('/customer/dashboard');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '01812345678',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/auth/login', [
            'login' => 'buyer@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The provided credentials do not match our records.');
        $this->assertGuest();
    }

    public function test_register_attaches_guest_cart_to_customer(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $cart = Cart::factory()->create(['user_id' => null, 'session_id' => 'guest-cart-auth', 'status' => CartStatus::Active]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'price_snapshot' => ['unit_price' => 100000],
        ]);

        $this->withHeader('X-Cart-Session', 'guest-cart-auth')->postJson('/auth/register', [
            'name' => 'Sakil Ahmed',
            'email' => 'sakil@example.com',
            'phone' => '01712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $user = User::query()->where('email', 'sakil@example.com')->firstOrFail();
        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'user_id' => $user->id, 'status' => CartStatus::Active->value]);
    }
}
