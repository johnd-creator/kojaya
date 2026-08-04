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
    public function __construct(string $message, public readonly ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public static function payloadMismatch(string $message): self
    {
        return new self($message);
    }

    public static function terminalState(string $message): self
    {
        return new self($message);
    }

    public static function loanAmountStale(string $message): self
    {
        return new self($message, 'LOAN_PAYMENT_INTENT_AMOUNT_STALE');
    }

    public static function loanReconciliationRequired(string $message): self
    {
        return new self($message, 'LOAN_PAYMENT_INTENT_RECONCILIATION_REQUIRED');
    }

    public static function loanAlreadyPaid(string $message): self
    {
        return new self($message, 'LOAN_PAYMENT_INTENT_ALREADY_PAID');
    }
}
