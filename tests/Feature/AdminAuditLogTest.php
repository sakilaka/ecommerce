<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_audit_logs(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Aurelia Admin']);
        $product = Product::factory()->create();
        $log = AuditLog::factory()->for($admin)->create([
            'event' => 'product.updated',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'active'],
            'ip_address' => '127.0.0.1',
        ]);
        AuditLog::factory()->create(['event' => 'store_settings.updated']);

        $response = $this->actingAs($admin)->getJson('/api/admin/audit-logs?q=aurelia&event=product.updated');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $log->id)
            ->assertJsonPath('data.0.event', 'product.updated')
            ->assertJsonPath('data.0.auditable_label', 'Product')
            ->assertJsonPath('data.0.user.name', 'Aurelia Admin')
            ->assertJsonPath('data.0.new_values.status', 'active')
            ->assertJsonStructure(['data' => [['id', 'event', 'auditable_type', 'auditable_label', 'auditable_id', 'old_values', 'new_values', 'user']], 'links', 'meta']);
    }

    public function test_store_settings_update_creates_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings', $this->validSettingsPayload([
            'identity' => ['store_name' => 'Aurelia Audit'],
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'store_settings.updated',
            'auditable_type' => StoreSetting::class,
        ]);
        $this->assertSame('Aurelia Audit', AuditLog::query()->where('event', 'store_settings.updated')->first()?->new_values['identity']['store_name']);
    }

    public function test_audit_log_filters_validate_input(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/audit-logs?per_page=100&user_id=999999');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page', 'user_id']);
    }

    public function test_customer_cannot_view_audit_logs(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/audit-logs')->assertForbidden();
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, array<string, mixed>>
     */
    private function validSettingsPayload(array $overrides = []): array
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
