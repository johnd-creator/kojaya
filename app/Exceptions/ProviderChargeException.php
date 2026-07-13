<?php

namespace App\Exceptions;

use App\Enums\ProviderChargeOutcome;
use RuntimeException;

/**
 * Typed exception carrying the classified outcome of a provider charge
 * creation attempt. Thrown by the payment gateway layer so that the
 * charge service can apply the correct recovery behaviour.
 */
class ProviderChargeException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ProviderChargeOutcome $outcome,
        public readonly ?int $httpStatus = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function unknown(string $message, ?int $httpStatus = null, ?\Throwable $previous = null): self
    {
        return new self($message, ProviderChargeOutcome::Unknown, $httpStatus, $previous);
    }

    public static function rejected(string $message, ?int $httpStatus = null, ?\Throwable $previous = null): self
    {
        return new self($message, ProviderChargeOutcome::DefinitiveRejected, $httpStatus, $previous);
    }

    public static function notCreated(string $message, ?int $httpStatus = null, ?\Throwable $previous = null): self
    {
        return new self($message, ProviderChargeOutcome::DefinitiveNotCreated, $httpStatus, $previous);
    }
}
