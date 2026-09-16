<?php

namespace App\Services\Cooperative;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * @implements ArrayAccess<string, mixed>
 */
final class ImportValidationResult implements ArrayAccess, JsonSerializable
{
    /**
     * @param  list<ImportValidationError>  $errors
     * @param  list<ImportRowResult>  $rows
     */
    public function __construct(
        public readonly bool $valid,
        public readonly bool $headerValid,
        public readonly int $totalRows,
        public readonly int $validRows,
        public readonly int $invalidRows,
        public readonly array $errors,
        public readonly array $rows,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'header_valid' => $this->headerValid,
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'errors' => array_map(fn (ImportValidationError $error): array => $error->toArray(), $this->errors),
            'rows' => array_map(fn (ImportRowResult $row): array => $row->toArray(), $this->rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('ImportValidationResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('ImportValidationResult is immutable.');
    }
}
