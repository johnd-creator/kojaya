<?php

namespace App\Services\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MemberStatusTransitionService
{
    public function __construct(
        private readonly MemberAccessRevocationService $accessRevocation,
        private readonly AuditLogService $audit,
    ) {}

    public function deactivate(CooperativeMember $member, User $actor, ?string $reason = null): CooperativeMember
    {
        $this->assertActorCan($actor, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        return $this->applyTransition(
            $member,
            [['ACTIVE', CooperativeMember::VALIDATION_ACTIVE]],
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_INACTIVE,
            $actor,
            'deactivated',
            $reason,
        );
    }

    public function resign(CooperativeMember $member, User $actor, ?string $reason = null): CooperativeMember
    {
        $this->assertActorCan($actor, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        return $this->applyTransition(
            $member,
            [['ACTIVE', CooperativeMember::VALIDATION_ACTIVE]],
            CooperativeMember::VALIDATION_RESIGNED,
            CooperativeMember::VALIDATION_RESIGNED,
            $actor,
            'resigned',
            $reason,
            ['resigned_at' => now()->toDateString()],
        );
    }

    /** @param array<string, mixed> $attributes */
    public function activate(CooperativeMember $member, User $actor, array $attributes = []): CooperativeMember
    {
        $this->assertActorCan($actor, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        return $this->applyTransition(
            $member,
            [['INACTIVE', CooperativeMember::VALIDATION_INACTIVE]],
            CooperativeMember::VALIDATION_ACTIVE,
            CooperativeMember::VALIDATION_ACTIVE,
            $actor,
            'activated',
            'Member activated.',
            $attributes,
            assignMemberRole: true,
            revokeMemberTokens: false,
        );
    }

    public function verifyByAdmin(CooperativeMember $member, User $actor, ?string $reason = null, array $attributes = []): CooperativeMember
    {
        $this->assertActorCanAny($actor, [
            PermissionEnum::COOPERATIVE_MEMBER_VERIFY->value,
            PermissionEnum::COOPERATIVE_MEMBER_VALIDATE->value,
        ]);

        return $this->applyTransition(
            $member,
            [
                ['PENDING', CooperativeMember::VALIDATION_PENDING],
                ['INACTIVE', CooperativeMember::VALIDATION_REVISION],
            ],
            'PENDING',
            CooperativeMember::VALIDATION_PENDING_REVIEW,
            $actor,
            'admin_verified',
            $reason,
            $attributes,
        );
    }

    public function approveFinal(CooperativeMember $member, User $actor, ?string $reason = null, array $attributes = []): CooperativeMember
    {
        $this->assertActorCan($actor, PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value);

        return $this->applyTransition(
            $member,
            [['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW]],
            CooperativeMember::VALIDATION_ACTIVE,
            CooperativeMember::VALIDATION_ACTIVE,
            $actor,
            'approved',
            $reason,
            $attributes,
            assignMemberRole: true,
            revokeMemberTokens: false,
        );
    }

    public function requestRevision(CooperativeMember $member, User $actor, ?string $reason = null, array $attributes = []): CooperativeMember
    {
        $this->assertActorCanAny($actor, [
            PermissionEnum::COOPERATIVE_MEMBER_VERIFY->value,
            PermissionEnum::COOPERATIVE_MEMBER_VALIDATE->value,
        ]);

        return $this->applyTransition(
            $member,
            [['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW]],
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REVISION,
            $actor,
            'revision_requested',
            $reason,
            $attributes,
        );
    }

    public function reject(CooperativeMember $member, User $actor, ?string $reason = null, array $attributes = []): CooperativeMember
    {
        $this->assertActorCanAny($actor, [
            PermissionEnum::COOPERATIVE_MEMBER_VERIFY->value,
            PermissionEnum::COOPERATIVE_MEMBER_VALIDATE->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
        ]);

        return $this->applyTransition(
            $member,
            [['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW]],
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REJECTED,
            $actor,
            'rejected',
            $reason,
            $attributes,
        );
    }

    /**
     * Revoke all member access without changing lifecycle status.
     * Used when a member is soft-deleted or unlinked from their user account.
     */
    public function deleteAccess(CooperativeMember $member, User $actor, ?string $reason = null): CooperativeMember
    {
        $this->assertActorCan($actor, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        return DB::transaction(function () use ($member, $actor, $reason): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);

            $member->user?->removeRole('Anggota');

            $this->audit->log('member.access.deleted', 'cooperative.lifecycle', $member, [
                'old' => ['status' => $member->status, 'validation_status' => $member->validation_status],
                'new' => ['action' => 'delete_access'],
                'reason' => $reason ?? 'Member access revoked.',
            ], AuditContext::forActor($actor));

            $this->accessRevocation->revokeAfterCommit($member, 'delete_access', $actor);

            return $member->refresh();
        });
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $allowedSources
     * @param  array<string, mixed>  $attributes
     */
    private function applyTransition(
        CooperativeMember $member,
        array $allowedSources,
        string $status,
        string $validationStatus,
        User $actor,
        string $action,
        ?string $reason = null,
        array $attributes = [],
        bool $assignMemberRole = false,
        bool $revokeMemberTokens = true,
    ): CooperativeMember {
        return DB::transaction(function () use ($member, $allowedSources, $status, $validationStatus, $actor, $action, $reason, $attributes, $assignMemberRole, $revokeMemberTokens): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $this->assertAllowedSource($member, $allowedSources);
            $oldState = ['status' => $member->status, 'validation_status' => $member->validation_status];
            $member->forceFill([
                ...$attributes,
                'status' => $status,
                'validation_status' => $validationStatus,
            ])->save();

            if ($assignMemberRole && $member->user && ! $member->user->hasRole('Anggota')) {
                $member->user->assignRole(Role::query()->firstOrCreate(['name' => 'Anggota']));
            }

            if (! $assignMemberRole) {
                $member->user?->removeRole('Anggota');
            }

            $this->audit->log('member.status.transitioned', 'cooperative.lifecycle', $member, [
                'old' => $oldState,
                'new' => ['status' => $status, 'validation_status' => $validationStatus, 'action' => $action],
                'reason' => $reason ?? $action,
            ], AuditContext::forActor($actor));

            if ($revokeMemberTokens) {
                $this->accessRevocation->revokeAfterCommit($member, $action, $actor);
            }

            return $member->refresh();
        });
    }

    /**
     * Assert the actor has the required permission.
     * Throws AuthorizationException BEFORE any mutation, role update, audit, or token revocation.
     */
    private function assertActorCan(User $actor, string $permission): void
    {
        if (! $actor->can($permission)) {
            throw new AuthorizationException(
                "Actor lacks required permission [{$permission}] for this lifecycle command."
            );
        }
    }

    /**
     * Assert the actor has at least one of the required permissions.
     */
    private function assertActorCanAny(User $actor, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($actor->can($permission)) {
                return;
            }
        }

        throw new AuthorizationException(
            'Actor lacks required permission for this lifecycle command.'
        );
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $allowedSources
     */
    private function assertAllowedSource(CooperativeMember $member, array $allowedSources): void
    {
        foreach ($allowedSources as [$status, $validationStatus]) {
            if ($member->status === $status && $member->validation_status === $validationStatus) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'status' => 'Transisi lifecycle tidak valid dari state anggota saat ini.',
        ]);
    }
}
