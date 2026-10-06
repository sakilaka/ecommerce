<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BackupCommerceData extends Command
{
    protected $signature = 'commerce:backup {--keep-days= : Override configured retention window}';

    protected $description = 'Create a local database backup snapshot for commerce operations.';

    public function handle(): int
    {
        if (! (bool) config('commerce_maintenance.backup.enabled')) {
            $this->warn('Commerce backup is disabled.');

            return self::SUCCESS;
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            $this->warn("Automatic local backup currently supports sqlite connections only. Configure managed database snapshots for {$driver}.");

            return self::SUCCESS;
        }

        $databasePath = (string) config("database.connections.{$connection}.database");

        if ($databasePath === ':memory:' || ! is_file($databasePath)) {
            throw new RuntimeException('SQLite database file is not available for backup.');
        }

        $disk = Storage::disk((string) config('commerce_maintenance.backup.disk', 'local'));
        $directory = trim((string) config('commerce_maintenance.backup.path', 'backups/commerce'), '/');
        $timestamp = now()->format('Ymd_His');
        $name = Str::slug((string) config('app.name', 'commerce'));
        $backupPath = "{$directory}/{$name}-{$timestamp}.sqlite";
        $manifestPath = "{$directory}/{$name}-{$timestamp}.json";

        $disk->put($backupPath, file_get_contents($databasePath));
        $disk->put($manifestPath, json_encode([
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'connection' => $connection,
            'driver' => $driver,
            'database' => basename($databasePath),
            'backup' => $backupPath,
            'size_bytes' => filesize($databasePath),
            'generated_at' => now()->toISOString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->pruneOldBackups($disk, $directory);

        $this->info("Commerce backup created: {$backupPath}");

        return self::SUCCESS;
    }

    private function pruneOldBackups(mixed $disk, string $directory): void
    {
        $keepDays = (int) ($this->option('keep-days') ?: config('commerce_maintenance.backup.retention_days', 14));

        if ($keepDays <= 0) {
            return;
        }

        $cutoff = now()->subDays($keepDays);

        foreach ($disk->files($directory) as $file) {
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));

            if ($lastModified->lt($cutoff)) {
                $disk->delete($file);
            }
        }
    }
}
