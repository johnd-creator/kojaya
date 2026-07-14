<?php

namespace App\Exceptions;

use RuntimeException;

class PiiDecryptionException extends RuntimeException
{
    public static function forField(string $field, ?string $version): self
    {
        return new self("Unable to decrypt PII field [{$field}] with key version [{$version}].");
    }
}
