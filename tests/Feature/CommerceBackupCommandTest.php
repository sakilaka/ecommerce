<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommerceBackupCommandTest extends TestCase
{
    public function test_sqlite_database_backup_is_created_with_manifest(): void
    {
        Storage::fake('local');

        $databasePath = storage_path('framework/testing-commerce-backup.sqlite');
        file_put_contents($databasePath, 'sqlite-backup-content');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
            'commerce_maintenance.backup.enabled' => true,
            'commerce_maintenance.backup.disk' => 'local',
            'commerce_maintenance.backup.path' => 'testing-commerce-backups',
            'commerce_maintenance.backup.retention_days' => 14,
        ]);

        $this->artisan('commerce:backup')
            ->expectsOutputToContain('Commerce backup created:')
            ->assertSuccessful();

        $files = Storage::disk('local')->files('testing-commerce-backups');

        $this->assertCount(2, $files);
        $this->assertNotEmpty(preg_grep('/\.sqlite$/', $files));
        $this->assertNotEmpty(preg_grep('/\.json$/', $files));

        @unlink($databasePath);
    }

    public function test_backup_command_skips_unsupported_database_drivers(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'commerce_maintenance.backup.enabled' => true,
        ]);

        $this->artisan('commerce:backup')
            ->expectsOutputToContain('Automatic local backup currently supports sqlite connections only.')
            ->assertSuccessful();
    }
}
