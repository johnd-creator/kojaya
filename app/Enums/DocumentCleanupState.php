<?php

namespace App\Enums;

enum DocumentCleanupState: string
{
    case ConfirmedPresent = 'confirmed_present';
    case ConfirmedAbsent = 'confirmed_absent';
    case Unknown = 'unknown';

    public function isConfirmedAbsent(): bool
    {
        return $this === self::ConfirmedAbsent;
    }

    public function isConfirmedPresent(): bool
    {
        return $this === self::ConfirmedPresent;
    }

    public function isAmbiguous(): bool
    {
        return $this === self::Unknown;
    }

    public function isUnknown(): bool
    {
        return $this === self::Unknown;
    }
}
