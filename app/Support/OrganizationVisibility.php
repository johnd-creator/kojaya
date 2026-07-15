<?php

namespace App\Support;

use App\Enums\OrganizationVisibilityState;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Explicit, fail-closed organization visibility scope.
 *
 * Replaces the ambiguous `?string $organizationId` pattern where null meant
 * both "global" and "no organization set". This value object makes intent
 * unambiguous: either the caller has global visibility, or it is scoped to
 * exactly one organization. There is no silent unscoped query path.
 */
final readonly class OrganizationVisibility
{
    public readonly bool $global;

    private function __construct(
        public OrganizationVisibilityState $state,
        public ?string $organizationId,
    ) {
        $this->global = $state === OrganizationVisibilityState::GLOBAL;
    }

    public static function global(): self
    {
        return new self(OrganizationVisibilityState::GLOBAL, null);
    }

    public static function organization(string $organizationId): self
    {
        if ($organizationId === '') {
            throw new InvalidArgumentException('An organization id is required for scoped visibility.');
        }

        return new self(OrganizationVisibilityState::ORGANIZATION, $organizationId);
    }

    public static function denied(): self
    {
        return new self(OrganizationVisibilityState::DENIED, null);
    }

    /**
     * Apply this scope to a query builder.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<T>  $query
     * @return Builder<T>
     */
    public function applyTo(Builder $query): Builder
    {
        if ($this->state === OrganizationVisibilityState::GLOBAL) {
            return $query;
        }

        if ($this->state === OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('Organization visibility is denied.');
        }

        return $query->where('organization_id', $this->organizationId);
    }
}
