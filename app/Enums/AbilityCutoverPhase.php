<?php

namespace App\Enums;

enum AbilityCutoverPhase: string
{
    case INSTRUMENT = 'instrument';
    case ROTATE = 'rotate';
    case DEPRECATE = 'deprecate';
    case REMOVE = 'remove';

    public static function fromConfiguration(mixed $value): self
    {
        $normalized = strtolower(trim((string) $value));
        $phase = self::tryFrom($normalized);

        if ($phase === null) {
            throw new \InvalidArgumentException("Invalid ability cutover phase [{$normalized}].");
        }

        return $phase;
    }
}
