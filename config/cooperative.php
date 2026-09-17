<?php

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
];
