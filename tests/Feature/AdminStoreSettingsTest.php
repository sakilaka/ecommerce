<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_default_store_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/settings');

        $response->assertOk()
            ->assertJsonPath('data.identity.store_name', 'Aurelia')
            ->assertJsonPath('data.checkout.cod_enabled', true)
            ->assertJsonPath('data.delivery.free_delivery_threshold', 199900)
            ->assertJsonPath('data.checkout.bkash_merchant_number', '01700000000')
            ->assertJsonPath('data.checkout.nagad_merchant_number', '01800000000')
            ->assertJsonPath('payment_readiness.methods.cod.status', 'ready')
            ->assertJsonPath('payment_readiness.methods.sslcommerz.status', 'disabled')
            ->assertJsonPath('data.operations.overselling_allowed', true)
            ->assertJsonStructure(['data' => ['identity', 'checkout', 'delivery', 'returns', 'operations']]);
    }

    public function test_admin_can_update_store_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings', $this->validPayload([
            'identity' => ['store_name' => 'Aurelia BD'],
            'delivery' => ['free_delivery_threshold' => 249900],
            'checkout' => [
                'bkash_enabled' => true,
                'bkash_merchant_number' => '01711112222',
                'bkash_account_type' => 'merchant',
            ],
            'operations' => ['low_stock_threshold' => 8],
        ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Store settings updated.')
            ->assertJsonPath('data.identity.store_name', 'Aurelia BD')
            ->assertJsonPath('data.delivery.free_delivery_threshold', 249900)
            ->assertJsonPath('data.checkout.bkash_enabled', true)
            ->assertJsonPath('data.checkout.bkash_merchant_number', '01711112222')
            ->assertJsonPath('data.operations.low_stock_threshold', 8);

        $this->assertDatabaseHas('store_settings', [
            'key' => 'identity.store_name',
            'setting_group' => 'identity',
        ]);
        $this->assertSame('Aurelia BD', StoreSetting::query()->where('key', 'identity.store_name')->first()?->value['value']);
    }

    public function test_payment_readiness_flags_enabled_methods_missing_setup(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings', $this->validPayload([
            'checkout' => [
                'bkash_enabled' => true,
                'bkash_merchant_number' => '',
                'nagad_enabled' => true,
                'nagad_merchant_number' => '01811112222',
                'sslcommerz_enabled' => true,
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('payment_readiness.overall_status', 'needs_setup')
            ->assertJsonPath('payment_readiness.methods.bkash.status', 'needs_setup')
            ->assertJsonPath('payment_readiness.methods.bkash.missing.0', 'bKash merchant number')
            ->assertJsonPath('payment_readiness.methods.nagad.status', 'ready')
            ->assertJsonPath('payment_readiness.methods.sslcommerz.status', 'needs_setup');
    }

    public function test_store_settings_validation_rejects_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings', $this->validPayload([
            'identity' => ['support_email' => 'not-email'],
            'delivery' => ['inside_dhaka_charge' => -1],
            'returns' => ['return_window_days' => 90],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'identity.support_email',
                'delivery.inside_dhaka_charge',
                'returns.return_window_days',
            ]);
    }

    public function test_customer_cannot_manage_store_settings(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/settings')->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/settings', $this->validPayload())->assertForbidden();
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, array<string, mixed>>
     */
    private function validPayload(array $overrides = []): array
    {
        $payload = [
            'identity' => [
                'store_name' => 'Aurelia',
                'tagline' => 'Premium Bangladesh Market',
                'support_phone' => '09612-345678',
                'support_email' => 'support@aurelia.test',
                'address' => 'Dhaka, Bangladesh',
            ],
            'checkout' => [
                'cod_enabled' => true,
                'sslcommerz_enabled' => false,
                'bkash_enabled' => false,
                'nagad_enabled' => false,
                'card_enabled' => false,
                'bkash_merchant_number' => '01700000000',
                'bkash_account_type' => 'merchant',
                'nagad_merchant_number' => '01800000000',
                'nagad_account_type' => 'merchant',
            ],
            'delivery' => [
                'free_delivery_threshold' => 199900,
                'inside_dhaka_charge' => 7000,
                'outside_dhaka_charge' => 12000,
                'cod_charge' => 0,
            ],
            'returns' => [
                'return_window_days' => 7,
                'refund_support_enabled' => true,
            ],
            'operations' => [
                'overselling_allowed' => true,
                'auto_confirm_cod_orders' => false,
                'low_stock_threshold' => 5,
            ],
        ];

        foreach ($overrides as $group => $values) {
            $payload[$group] = array_replace($payload[$group], $values);
        }

        return $payload;
    }
}
