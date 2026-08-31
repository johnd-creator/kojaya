<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentGatewayUnavailableException extends RuntimeException
{
    public function __construct(
        string $message = 'Payment gateway provider is not configured or currently unavailable.',
        public readonly int $statusCode = 503,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
