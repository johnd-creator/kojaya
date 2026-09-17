<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class MemberImportExecutionException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?array $errors = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
