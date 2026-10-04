<?php

namespace App\Support;

class QaRuntimeFileCreationMask
{
    public static function apply(string $environment, string $sapi): void
    {
        if ($environment === 'qa' && $sapi === 'fpm-fcgi') {
            umask(0007);
        }
    }
}
