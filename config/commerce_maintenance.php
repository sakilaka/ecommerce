<?php

return [
    'backup' => [
        'enabled' => (bool) env('COMMERCE_BACKUP_ENABLED', true),
        'disk' => env('COMMERCE_BACKUP_DISK', 'local'),
        'path' => env('COMMERCE_BACKUP_PATH', 'backups/commerce'),
        'retention_days' => (int) env('COMMERCE_BACKUP_RETENTION_DAYS', 14),
        'schedule_time' => env('COMMERCE_BACKUP_SCHEDULE_TIME', '02:30'),
    ],

    'deployment' => [
        'build_manifest' => env('COMMERCE_BUILD_MANIFEST', 'public/build/manifest.json'),
        'require_storage_link' => (bool) env('COMMERCE_REQUIRE_STORAGE_LINK', true),
    ],
];
