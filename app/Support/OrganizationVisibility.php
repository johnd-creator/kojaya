<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class OrganizationVisibility
{
    private function __construct(
        public bool $global,
        public ?string $organizationId,
    ) {}

    public static function global(): self
    {
        return new self(true, null);
    }

    public static function organization(string $organizationId): self
    {
        if ($organizationId === '') {
            throw new InvalidArgumentException('An organization id is required for scoped visibility.');
        }

        return new self(false, $organizationId);
    }
}
