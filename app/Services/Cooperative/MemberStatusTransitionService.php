<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MemberStatusTransitionService
{
    public function __construct(
        private readonly MemberAccessRevocationService $accessRevocation,
        private readonly AuditLogService $audit,
    ) {}

    public function deactivate(CooperativeMember $member, ?User $actor = null, ?string $reason = null): CooperativeMember
    {
        return $this->applyTransition(
            $member,
            [
                ['ACTIVE', CooperativeMember::VALIDATION_ACTIVE],
                ['ACTIVE', CooperativeMember::VALIDATION_PENDING],
            ],
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_INACTIVE,
            $actor,
            'deactivated',
            $reason,
        );
    }

    public function resign(CooperativeMember $member, ?User $actor = null, ?string $reason = null): CooperativeMember
    {
        return $this->applyTransition(
            $member,
            [
                ['ACTIVE', CooperativeMember::VALIDATION_ACTIVE],
                ['ACTIVE', CooperativeMember::VALIDATION_PENDING],
            ],
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
        return $this->applyTransition(
            $member,
            [
                ['INACTIVE', CooperativeMember::VALIDATION_INACTIVE],
                ['INACTIVE', CooperativeMember::VALIDATION_PENDING],
                ['PENDING', CooperativeMember::VALIDATION_PENDING],
            ],
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
        return $this->applyTransition(
            $member,
            [
                ['PENDING', CooperativeMember::VALIDATION_PENDING],
                ['ACTIVE', CooperativeMember::VALIDATION_PENDING],
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
        return $this->applyTransition(
            $member,
            [
                ['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW],
                ['ACTIVE', CooperativeMember::VALIDATION_PENDING],
                ['PENDING', CooperativeMember::VALIDATION_PENDING],
            ],
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
        return $this->applyTransition(
            $member,
            [
                ['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW],
                ['ACTIVE', CooperativeMember::VALIDATION_PENDING],
                ['PENDING', CooperativeMember::VALIDATION_PENDING],
            ],
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REJECTED,
            $actor,
            'rejected',
            $reason,
            $attributes,
        );
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
        ?User $actor,
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
            ]);

            if ($revokeMemberTokens && $actor !== null) {
                $this->accessRevocation->revokeAfterCommit($member, $action, $actor);
            }

            return $member->refresh();
        });
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $allowedSources
     */
    private function assertAllowedSource(CooperativeMember $member, array $allowedSources): void
    {
        foreach ($allowedSources as [$status, $validationStatus]) {
            if ($member->status === $status && ($member->validation_status === $validationStatus || $member->validation_status === null)) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'status' => 'Transisi lifecycle tidak valid dari state anggota saat ini.',
        ]);
    }
}
