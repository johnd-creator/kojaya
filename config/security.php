<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PII blind-index key
    |--------------------------------------------------------------------------
    |
    | Keep this key in the deployment secret manager. The application key is
    | used only as a local-development fallback so tests and fresh installs do
    | not silently produce an unusable index.
    |
    */
    'blind_index_key' => env('PII_BLIND_INDEX_KEY', env('APP_KEY', '')),
    'blind_index_version' => env('PII_BLIND_INDEX_VERSION', 'v1'),
];
