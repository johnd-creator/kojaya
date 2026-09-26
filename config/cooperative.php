<?php

$defaultBootstrapOrganizationName = in_array(
    env('APP_ENV', 'production'),
    ['production', 'staging', 'qa'],
    true,
) ? null : 'Koperasi Jaya Bersama';

return [
    /*
    |--------------------------------------------------------------------------
    | Member Import Execution Feature Gate (DEV Only)
    |--------------------------------------------------------------------------
    |
    | When set to false, actual persistence of member imports (ONB-06) is blocked.
    | The dry-run preview (ONB-05) remains fully operational.
    |
    */
    'member_import_execution_enabled' => (bool) env('COOPERATIVE_MEMBER_IMPORT_EXECUTION_ENABLED', false),

    'bootstrap_organization' => [
        'name' => env('COOPERATIVE_BOOTSTRAP_ORGANIZATION_NAME', $defaultBootstrapOrganizationName),
        'address' => env('COOPERATIVE_BOOTSTRAP_ORGANIZATION_ADDRESS'),
        'phone' => env('COOPERATIVE_BOOTSTRAP_ORGANIZATION_PHONE'),
        'email' => env('COOPERATIVE_BOOTSTRAP_ORGANIZATION_EMAIL'),
    ],
];
