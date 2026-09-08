<?php

namespace App\Support;

use App\Enums\OrganizationVisibilityState;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

final readonly class ReportAuthorizationScope
{
    public const MODE_ORGANIZATION = 'organization';

    public const MODE_GLOBAL = 'global';

    public function __construct(
        public int $version,
        public string $mode,
        public ?string $organizationId = null,
    ) {
        if ($this->version !== 1) {
            throw new InvalidArgumentException("Unsupported report scope version [{$this->version}].");
        }

        if ($this->mode === self::MODE_ORGANIZATION) {
            if ($this->organizationId === null || $this->organizationId === '') {
                throw new InvalidArgumentException('Organization id is required for organization report scope.');
            }
        } elseif ($this->mode === self::MODE_GLOBAL) {
            if ($this->organizationId !== null) {
                throw new InvalidArgumentException('Organization id must be null for global report scope.');
            }
        } else {
            throw new InvalidArgumentException("Invalid report scope mode [{$this->mode}].");
        }
    }

    public static function forVisibility(OrganizationVisibility $visibility): self
    {
        if ($visibility->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('Cannot create report scope for denied visibility.');
        }

        if ($visibility->global) {
            return new self(1, self::MODE_GLOBAL, null);
        }

        if ($visibility->organizationId === null || $visibility->organizationId === '') {
            throw new AuthorizationException('Cannot create report scope without a valid organization id.');
        }

        return new self(1, self::MODE_ORGANIZATION, (string) $visibility->organizationId);
    }

    public static function fromArray(mixed $data): self
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException('Report scope metadata must be an array.');
        }

        $version = $data['version'] ?? null;
        $mode = $data['mode'] ?? null;
        $organizationId = $data['organization_id'] ?? null;

        if ($version !== 1 || ! is_string($mode)) {
            throw new InvalidArgumentException('Malformed report scope metadata.');
        }

        return new self(
            version: (int) $version,
            mode: $mode,
            organizationId: $organizationId !== null ? (string) $organizationId : null,
        );
    }

    /**
     * @return array{version: int, mode: string, organization_id: ?string}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'mode' => $this->mode,
            'organization_id' => $this->organizationId,
        ];
    }

    public function isGlobal(): bool
    {
        return $this->mode === self::MODE_GLOBAL;
    }

    public function isOrganization(): bool
    {
        return $this->mode === self::MODE_ORGANIZATION;
    }

    public function isAuthorized(OrganizationVisibility $visibility): bool
    {
        if ($visibility->state === OrganizationVisibilityState::DENIED) {
            return false;
        }

        if ($visibility->global) {
            return true;
        }

        if ($this->isGlobal()) {
            return false;
        }

        return (string) $this->organizationId === (string) $visibility->organizationId;
    }

    public function intersect(OrganizationVisibility $visibility): OrganizationVisibility
    {
        if ($visibility->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('Organization visibility is denied.');
        }

        if (! $this->isAuthorized($visibility)) {
            if ($this->isGlobal() && ! $visibility->global) {
                throw new AuthorizationException('Stored global report scope exceeds current organization scope.');
            }

            if ($this->isOrganization() && (string) $this->organizationId !== (string) $visibility->organizationId) {
                throw new AuthorizationException('Stored report organization scope does not match current user organization.');
            }

            throw new AuthorizationException('Report authorization scope is not accessible under current visibility.');
        }

        if ($this->isOrganization()) {
            return OrganizationVisibility::organization((string) $this->organizationId);
        }

        return OrganizationVisibility::global();
    }

    public function toVisibility(): OrganizationVisibility
    {
        return $this->isGlobal()
            ? OrganizationVisibility::global()
            : OrganizationVisibility::organization((string) $this->organizationId);
    }
}
