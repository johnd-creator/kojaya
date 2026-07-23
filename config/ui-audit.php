<?php

return [
    'fixed_now' => env('UI_AUDIT_FIXED_NOW', '2026-01-15T09:30:00+07:00'),
    'server_host' => env('UI_AUDIT_SERVER_HOST', '127.0.0.1'),
    'server_port' => (int) env('UI_AUDIT_SERVER_PORT', 18080),
    'allowed_environments' => ['testing', 'playwright'],
    'fixture_path' => '/__ui-audit/fixtures',
];
