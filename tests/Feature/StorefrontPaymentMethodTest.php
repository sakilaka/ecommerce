<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_lists_configured_payment_methods(): void
    {
        config()->set('payment_methods.methods.bkash.enabled', true);

        $response = $this->getJson('/api/storefront/payment-methods');

        $response->assertOk()
            ->assertJsonPath('data.0.value', 'cod')
            ->assertJsonPath('data.0.enabled', true)
            ->assertJsonPath('data.0.next_action', 'confirm_order')
            ->assertJsonPath('data.2.value', 'bkash')
            ->assertJsonPath('data.2.enabled', true)
            ->assertJsonPath('data.2.next_action', 'manual_payment_instructions')
            ->assertJsonPath('data.2.checkout_ready', true)
            ->assertJsonPath('data.2.instruction_type', 'manual_mobile_payment')
            ->assertJsonPath('data.2.merchant_number', '01700000000')
            ->assertJsonStructure([
                'data' => [[
                    'value',
                    'enabled',
                    'gateway',
                    'label',
                    'label_bn',
                    'short_label',
                    'short_label_bn',
                    'description',
                    'description_bn',
                    'instruction_type',
                    'merchant_number',
                    'merchant_account_type',
                    'next_action',
                    'checkout_ready',
                ]],
            ]);
    }

    public function test_storefront_marks_enabled_gateway_without_credentials_as_not_ready(): void
    {
        config()->set('payment_methods.methods.sslcommerz.enabled', true);
        config()->set('payment_gateways.sslcommerz.store_id', null);
        config()->set('payment_gateways.sslcommerz.store_password', null);

        $response = $this->getJson('/api/storefront/payment-methods');

        $response->assertOk()
            ->assertJsonPath('data.1.value', 'sslcommerz')
            ->assertJsonPath('data.1.enabled', true)
            ->assertJsonPath('data.1.checkout_ready', false);
    }

    public function test_storefront_payment_methods_use_saved_admin_payment_settings(): void
    {
        config()->set('payment_methods.methods.bkash.enabled', false);

        StoreSetting::query()->create([
            'setting_group' => 'checkout',
            'key' => 'checkout.bkash_enabled',
            'value' => ['value' => true],
        ]);
        StoreSetting::query()->create([
            'setting_group' => 'checkout',
            'key' => 'checkout.bkash_merchant_number',
            'value' => ['value' => '01711112222'],
        ]);
        StoreSetting::query()->create([
            'setting_group' => 'checkout',
            'key' => 'checkout.bkash_account_type',
            'value' => ['value' => 'personal'],
        ]);

        $response = $this->getJson('/api/storefront/payment-methods');

        $response->assertOk()
            ->assertJsonPath('data.2.value', 'bkash')
            ->assertJsonPath('data.2.enabled', true)
            ->assertJsonPath('data.2.checkout_ready', true)
            ->assertJsonPath('data.2.merchant_number', '01711112222')
            ->assertJsonPath('data.2.merchant_account_type', 'personal');
    }
}
