<?php

namespace App\Services\Cooperative;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * @implements ArrayAccess<string, mixed>
 */
final class ImportRowResult implements ArrayAccess, JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    public readonly array $rawData;

    /**
     * @param  array<string, mixed>  $rawData
     * @param  array<string, mixed>  $normalizedData
     * @param  list<ImportValidationError>  $errors
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly bool $valid,
        array $rawData,
        public readonly array $normalizedData,
        public readonly ?int $resolvedEmployeeId,
        public readonly string $employeeResolutionStatus,
        public readonly bool $memberNumberGenerationRequired,
        public readonly bool $manualReviewRequired,
        public readonly bool $persistable,
        public readonly array $errors = [],
    ) {
        if (array_key_exists('identity_number', $rawData)) {
            $rawData['identity_number'] = '[REDACTED]';
        }
        $this->rawData = $rawData;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $sanitizedRawData = $this->rawData;
        if (array_key_exists('identity_number', $sanitizedRawData)) {
            $sanitizedRawData['identity_number'] = '[REDACTED]';
        }

        $sanitizedNormalizedData = $this->normalizedData;
        if (array_key_exists('identity_number', $sanitizedNormalizedData)) {
            $sanitizedNormalizedData['identity_number'] = '[REDACTED]';
        }

        return [
            'row_number' => $this->rowNumber,
            'valid' => $this->valid,
            'raw_data' => $sanitizedRawData,
            'normalized_data' => $sanitizedNormalizedData,
            'resolved_employee_id' => $this->resolvedEmployeeId,
            'employee_resolution_status' => $this->employeeResolutionStatus,
            'member_number_generation_required' => $this->memberNumberGenerationRequired,
            'manual_review_required' => $this->manualReviewRequired,
            'persistable' => $this->persistable,
            'errors' => array_map(fn (ImportValidationError $error): array => $error->toArray(), $this->errors),
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
        throw new LogicException('ImportRowResult is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('ImportRowResult is immutable.');
    }
}
