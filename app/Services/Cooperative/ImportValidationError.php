<?php

namespace App\Services\Cooperative;

use JsonSerializable;

final class ImportValidationError implements JsonSerializable
{
    public const SEVERITY_ERROR = 'ERROR';

    public const SEVERITY_FATAL = 'FATAL';

    public function __construct(
        public readonly ?int $row,
        public readonly string $field,
        public readonly string $code,
        public readonly string $message,
        public readonly string $severity = self::SEVERITY_ERROR,
    ) {}

    /**
     * @return array{row: ?int, field: string, code: string, message: string, severity: string}
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'field' => $this->field,
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
        ];
    }

    /**
     * @return array{row: ?int, field: string, code: string, message: string, severity: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
