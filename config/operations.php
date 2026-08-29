<?php

return [
    'retention' => [
        'log_days' => (int) env('LOG_RETENTION_DAYS', 30),
        'audit_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],

    'backup' => [
        'enabled' => (bool) env('BACKUP_ENABLED', true),
        'disk' => env('BACKUP_DISK', 'local'),
        'directory' => trim(env('BACKUP_DIRECTORY', 'backups/database'), '/'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
        'min_keep' => (int) env('BACKUP_MIN_KEEP', 1),
        'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 26),
        'offsite_enabled' => (bool) env('BACKUP_OFFSITE_ENABLED', false),
        'offsite_disk' => env('BACKUP_OFFSITE_DISK', null),
        'offsite_directory' => trim(env('BACKUP_OFFSITE_DIRECTORY', 'backups/database'), '/'),
        'require_offsite' => (bool) env('BACKUP_REQUIRE_OFFSITE', false),
        'timeout' => (int) env('BACKUP_TIMEOUT', 300),
    ],
];
