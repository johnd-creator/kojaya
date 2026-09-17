<?php

declare(strict_types=1);

namespace App\Services\Cooperative;

use ArrayAccess;
use JsonSerializable;

/**
 * @implements ArrayAccess<string, mixed>
 */
class MemberImportExecutionResult implements ArrayAccess, JsonSerializable
{
    /**
     * @param  list<string>  $generatedMemberNumbers
     * @param  list<string>  $suppliedMemberNumbers
     */
    public function __construct(
        public readonly string $importId,
        public readonly string $organizationId,
        public readonly string $importDate,
        public readonly int $totalRows,
        public readonly int $importedCount,
        public readonly array $generatedMemberNumbers,
        public readonly array $suppliedMemberNumbers,
        public readonly string $status = 'COMPLETED',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'import_id' => $this->importId,
            'organization_id' => $this->organizationId,
            'import_date' => $this->importDate,
            'total_rows' => $this->totalRows,
            'imported_count' => $this->importedCount,
            'generated_member_numbers' => $this->generatedMemberNumbers,
            'generated_member_number_count' => count($this->generatedMemberNumbers),
            'supplied_member_numbers' => $this->suppliedMemberNumbers,
            'supplied_member_number_count' => count($this->suppliedMemberNumbers),
            'status' => $this->status,
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
        // Read-only DTO
    }

    public function offsetUnset(mixed $offset): void
    {
        // Read-only DTO
    }
}
