<?php

return [
    // Local candidate defaults only. Enabling live remembered access requires separate approval.
    'enabled' => env('MEMBER_SESSION_V2_ENABLED', false),
    'access_minutes' => 15,
    'remembered_idle_minutes' => 30 * 24 * 60,
    'remembered_absolute_minutes' => 90 * 24 * 60,
    'normal_idle_minutes' => 30,
    'normal_absolute_minutes' => 12 * 60,
];
