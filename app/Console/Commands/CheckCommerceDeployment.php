<?php

namespace App\Console\Commands;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckCommerceDeployment extends Command
{
    protected $signature = 'commerce:deploy-check {--production : Enforce production launch requirements}';

    protected $description = 'Check whether the commerce application is ready for deployment.';

    /** @var array<int, array{status: string, item: string, detail: string}> */
    private array $results = [];

    public function handle(): int
    {
        $production = (bool) $this->option('production');

        $this->results = [];
        $this->checkAppConfiguration($production);
        $this->checkDatabaseState();
        $this->checkPublicAssets();
        $this->checkCommerceOperations($production);

        $this->table(['Status', 'Item', 'Detail'], $this->results);

        $failures = collect($this->results)->where('status', 'FAIL')->count();
        $warnings = collect($this->results)->where('status', 'WARN')->count();

        if ($failures > 0) {
            $this->error("Deployment check failed with {$failures} failure(s) and {$warnings} warning(s).");

            return self::FAILURE;
        }

        $this->info("Deployment check passed with {$warnings} warning(s).");

        return self::SUCCESS;
    }

    private function checkAppConfiguration(bool $production): void
    {
        $this->record(
            filled(config('app.key')),
            'Application key',
            filled(config('app.key')) ? 'APP_KEY is configured.' : 'APP_KEY is missing.',
        );

        if (! $production) {
            return;
        }

        $appUrl = (string) config('app.url');

        $this->record(app()->isProduction(), 'Environment', 'APP_ENV should be production for launch.');
        $this->record(! (bool) config('app.debug'), 'Debug mode', 'APP_DEBUG must be false in production.');
        $this->record(str_starts_with($appUrl, 'https://') && ! str_contains($appUrl, 'localhost'), 'Application URL', 'APP_URL should be the live HTTPS domain.');
        $this->record((bool) config('app.force_https'), 'Forced HTTPS', 'APP_FORCE_HTTPS should be true behind the production domain.');
        $this->record((bool) config('session.secure'), 'Secure session cookies', 'SESSION_SECURE_COOKIE should resolve to true in production.');
        $this->record(! in_array(config('queue.default'), ['sync', 'null'], true), 'Queue connection', 'Use database, redis, or another durable queue driver.');
        $this->record(config('cache.default') !== 'array', 'Cache store', 'Use database, redis, or another persistent cache store.');
    }

    private function checkDatabaseState(): void
    {
        try {
            DB::connection()->getPdo();
            $this->record(true, 'Database connection', 'Database connection is reachable.');
        } catch (Throwable $exception) {
            $this->record(false, 'Database connection', 'Database connection failed: '.$exception->getMessage());

            return;
        }

        $this->record(Schema::hasTable('migrations'), 'Migrations table', 'The migrations table must exist.');

        if (Schema::hasTable('users')) {
            $hasAdmin = User::query()
                ->where('role', UserRole::Admin)
                ->where('is_active', true)
                ->exists();

            $this->record($hasAdmin, 'Admin account', 'At least one active admin account is required.');
        } else {
            $this->record(false, 'Admin account', 'The users table is missing. Run migrations first.');
        }
    }

    private function checkPublicAssets(): void
    {
        $manifestPath = base_path((string) config('commerce_maintenance.deployment.build_manifest'));
        $storagePath = public_path('storage');

        $this->record(is_file($manifestPath), 'Frontend build manifest', 'Run npm run build before deployment.');

        if ((bool) config('commerce_maintenance.deployment.require_storage_link')) {
            $this->record(is_link($storagePath) || is_dir($storagePath) || readlink($storagePath) !== false, 'Public storage link', 'Run php artisan storage:link when serving public uploads locally.');
        }
    }

    private function checkCommerceOperations(bool $production): void
    {
        $methods = config('payment_methods.methods', []);
        $cod = $methods[PaymentMethod::CashOnDelivery->value]['enabled'] ?? false;
        $sslCommerzEnabled = $methods[PaymentMethod::SslCommerz->value]['enabled'] ?? false;

        $this->record((bool) $cod, 'Cash on Delivery', 'COD must stay enabled for Bangladesh-first checkout.');
        $this->record((bool) config('commerce_maintenance.backup.enabled'), 'Backup schedule', 'COMMERCE_BACKUP_ENABLED should be true before launch.');

        if ($production && $sslCommerzEnabled) {
            $storeId = config('payment_gateways.sslcommerz.store_id');
            $password = config('payment_gateways.sslcommerz.store_password');
            $sandbox = (bool) config('payment_gateways.sslcommerz.sandbox');

            $this->record(filled($storeId) && filled($password), 'SSLCommerz credentials', 'Live SSLCommerz store ID and password are required when enabled.');
            $this->record(! $sandbox, 'SSLCommerz sandbox', 'SSLCOMMERZ_SANDBOX must be false for live payments.');
        }
    }

    private function record(bool $passed, string $item, string $detail): void
    {
        $this->results[] = [
            'status' => $passed ? 'PASS' : 'FAIL',
            'item' => $item,
            'detail' => $detail,
        ];
    }
}
