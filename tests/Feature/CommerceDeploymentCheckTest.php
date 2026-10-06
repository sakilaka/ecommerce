<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommerceDeploymentCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_check_passes_when_required_launch_settings_are_ready(): void
    {
        $manifestPath = storage_path('framework/testing-build-manifest.json');
        file_put_contents($manifestPath, '{}');

        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->app['env'] = 'production';
        config([
            'app.key' => 'base64:'.str_repeat('a', 44),
            'app.debug' => false,
            'app.url' => 'https://shop.example.com',
            'app.force_https' => true,
            'session.secure' => true,
            'queue.default' => 'database',
            'cache.default' => 'database',
            'commerce_maintenance.backup.enabled' => true,
            'commerce_maintenance.deployment.build_manifest' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $manifestPath),
            'commerce_maintenance.deployment.require_storage_link' => false,
            'payment_methods.methods.cod.enabled' => true,
            'payment_methods.methods.sslcommerz.enabled' => false,
        ]);

        $this->artisan('commerce:deploy-check --production')
            ->expectsOutputToContain('Deployment check passed')
            ->assertSuccessful();

        @unlink($manifestPath);
    }

    public function test_deployment_check_fails_for_missing_production_requirements(): void
    {
        $this->app['env'] = 'local';
        config([
            'app.key' => '',
            'app.debug' => true,
            'app.url' => 'http://localhost:8000',
            'app.force_https' => false,
            'session.secure' => false,
            'queue.default' => 'sync',
            'cache.default' => 'array',
            'commerce_maintenance.backup.enabled' => false,
            'commerce_maintenance.deployment.build_manifest' => 'missing/manifest.json',
            'commerce_maintenance.deployment.require_storage_link' => false,
            'payment_methods.methods.cod.enabled' => false,
        ]);

        $this->artisan('commerce:deploy-check --production')
            ->expectsOutputToContain('Deployment check failed')
            ->assertFailed();
    }
}
