<?php

namespace App\Services\Cooperative;

use DateTimeInterface;
use Illuminate\Support\Str;

class PosReturnNumberGenerator
{
    public const PREFIX = 'RET-';

    /**
     * Generate a collision-proof POS return number with human-recognizable timestamp prefix.
     *
     * Format: RET-YYYYMMDD-HHMMSS-<ULID> (46 chars, fits in VARCHAR(60))
     */
    public function generate(?DateTimeInterface $timestamp = null): string
    {
        $time = $timestamp ? $timestamp->format('Ymd-His') : now()->format('Ymd-His');

        return self::PREFIX.$time.'-'.(string) Str::ulid();
    }
}
