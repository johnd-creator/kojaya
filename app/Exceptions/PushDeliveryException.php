<?php

namespace App\Exceptions;

use RuntimeException;

class PushDeliveryException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds = 60)
    {
        parent::__construct('Push notification delivery failed for one or more active Android tokens.');
    }
}
