<?php

return [
    'retention' => [
        'log_days' => (int) env('LOG_RETENTION_DAYS', 30),
        'audit_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],

    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'directory' => trim(env('BACKUP_DIRECTORY', 'backups/database'), '/'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    ],
];
