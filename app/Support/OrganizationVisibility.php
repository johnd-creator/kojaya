<?php

namespace App\Support;

use App\Enums\PermissionEnum;
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

    /**
     * Resolve visibility from a user's permissions and organization.
     *
     * @param  callable(string): bool  $can  Permission checker ($user->can(...) or equivalent).
     *
     * @throws AuthorizationException when a non-global user has no organization.
     */
    public static function fromUser(callable $can, ?string $organizationId): self
    {
        if ($can(PermissionEnum::COOPERATIVE_VIEW_ALL->value)) {
            return self::global();
        }

        if ($organizationId === null) {
            throw new AuthorizationException(
                'A cooperative organization is required for this operation.',
            );
        }

        return self::organization($organizationId);
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
        if ($this->global) {
            return $query;
        }

        return $query->where('organization_id', $this->organizationId);
    }
}
