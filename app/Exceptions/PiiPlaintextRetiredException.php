<?php

namespace App\Exceptions;

use RuntimeException;

final class PiiPlaintextRetiredException extends RuntimeException
{
    public static function forField(string $field): self
    {
        return new self("Plaintext PII access is retired for field [{$field}].");
    }
}
