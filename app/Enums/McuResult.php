<?php

namespace App\Enums;

enum McuResult: string
{
    case FIT = 'FIT';
    case FIT_WITH_RESTRICTION = 'FIT_WITH_RESTRICTION';
    case UNFIT = 'UNFIT';

    public function label(): string
    {
        return match ($this) {
            self::FIT => 'Fit',
            self::FIT_WITH_RESTRICTION => 'Fit with Restriction',
            self::UNFIT => 'Unfit',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FIT => 'green',
            self::FIT_WITH_RESTRICTION => 'yellow',
            self::UNFIT => 'red',
        };
    }

    public function isFitForWork(): bool
    {
        return $this !== self::UNFIT;
    }
}
