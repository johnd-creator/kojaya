<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MemberStatusTransitionService
{
    public function __construct(
        private readonly MemberAccessRevocationService $accessRevocation,
        private readonly AuditLogService $audit,
    ) {}

    public function deactivate(CooperativeMember $member, ?User $actor = null, ?string $reason = null): CooperativeMember
    {
        return $this->terminalTransition($member, CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_INACTIVE, $actor, 'deactivated', $reason);
    }

    public function resign(CooperativeMember $member, ?User $actor = null, ?string $reason = null): CooperativeMember
    {
        return $this->terminalTransition($member, CooperativeMember::VALIDATION_RESIGNED, CooperativeMember::VALIDATION_RESIGNED, $actor, 'resigned', $reason);
    }

    /** @param array<string, mixed> $attributes */
    public function activate(CooperativeMember $member, User $actor, array $attributes = []): CooperativeMember
    {
        return $this->transition(
            $member,
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function transition(
        CooperativeMember $member,
        string $status,
        string $validationStatus,
        User $actor,
        string $action,
        ?string $reason = null,
        array $attributes = [],
        bool $assignMemberRole = false,
        bool $revokeMemberTokens = true,
    ): CooperativeMember {
        return DB::transaction(function () use ($member, $status, $validationStatus, $actor, $action, $reason, $attributes, $assignMemberRole, $revokeMemberTokens): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
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

            if ($revokeMemberTokens) {
                $this->accessRevocation->revokeAfterCommit($member, $action, $actor);
            }

            return $member->refresh();
        });
    }

    private function terminalTransition(CooperativeMember $member, string $status, string $validationStatus, ?User $actor, string $action, ?string $reason): CooperativeMember
    {
        return DB::transaction(function () use ($member, $status, $validationStatus, $actor, $action, $reason): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $oldState = ['status' => $member->status, 'validation_status' => $member->validation_status];

            $member->forceFill([
                'status' => $status,
                'validation_status' => $validationStatus,
                'resigned_at' => $action === 'resigned' ? now()->toDateString() : null,
            ])->save();
            $member->user?->removeRole('Anggota');

            $this->audit->log('member.status.transitioned', 'cooperative.lifecycle', $member, [
                'old' => $oldState,
                'new' => ['status' => $status, 'validation_status' => $validationStatus, 'action' => $action],
                'reason' => $reason ?? $action,
            ]);
            $this->accessRevocation->revokeAfterCommit($member, $action, $actor);

            return $member->refresh();
        });
    }
}
