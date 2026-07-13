<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a client_reference conflicts with an existing intent that
 * has a different payload, or when the existing intent is in a terminal /
 * expired / settled state.
 *
 * Controllers map this to HTTP 409.
 */
class PaymentIntentConflictException extends RuntimeException
{
    public static function payloadMismatch(string $message): self
    {
        return new self($message);
    }

    public static function terminalState(string $message): self
    {
        return new self($message);
    }
}
