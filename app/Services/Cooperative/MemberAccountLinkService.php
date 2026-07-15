<?php

namespace App\Services\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MemberAccountLinkService
{
    /** @var list<string> */
    private const REASON_CODES = [
        'business_verification',
        'regulatory_request',
        'member_correction',
        'internal_audit',
        'other',
    ];

    public function __construct(
        private readonly OrganizationScopeService $scope,
        private readonly MemberAccessRevocationService $accessRevocation,
        private readonly AuditLogService $audit,
    ) {}

    public function link(User $actor, CooperativeMember $member, User $target, string $reason): CooperativeMember
    {
        $this->assertActorCanLink($actor, $member);
        $reasonCode = $this->reasonCode($reason);

        return DB::transaction(function () use ($member, $target, $reasonCode): CooperativeMember {
            $lockedMember = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->id);

            if ($lockedMember->user_id !== null) {
                throw ValidationException::withMessages([
                    'member' => 'Anggota sudah memiliki akun tertaut.',
                ]);
            }

            $this->assertTargetEligible($lockedMember, $lockedTarget);

            $lockedMember->forceFill(['user_id' => $lockedTarget->id])->save();

            Role::query()->firstOrCreate(['name' => 'Anggota']);
            $lockedTarget->refresh();
            if (! $lockedTarget->hasRole('Anggota')) {
                $lockedTarget->assignRole('Anggota');
            }

            $this->audit->log('member.account.linked', 'cooperative.member', $lockedMember, [
                'old' => ['user_id' => null],
                'new' => [
                    'user_id' => $lockedTarget->id,
                    'organization_id' => $lockedMember->organization_id,
                    'reason_code' => $reasonCode,
                    'reason_supplied' => true,
                ],
                'reason' => 'Controlled member account-link reason code.',
            ]);

            return $lockedMember->refresh();
        });
    }

    public function unlink(User $actor, CooperativeMember $member, string $reason): CooperativeMember
    {
        $this->assertActorCanLink($actor, $member);
        $reasonCode = $this->reasonCode($reason);

        return DB::transaction(function () use ($actor, $member, $reasonCode): CooperativeMember {
            $lockedMember = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $oldUserId = $lockedMember->user_id;

            if ($oldUserId === null) {
                throw ValidationException::withMessages([
                    'member' => 'Anggota tidak memiliki akun tertaut.',
                ]);
            }

            $oldUser = User::query()->lockForUpdate()->find($oldUserId);
            $lockedMember->forceFill(['user_id' => null])->save();

            if ($oldUser && ! $oldUser->cooperativeMember()->whereKeyNot($lockedMember->id)->exists()) {
                $oldUser->removeRole('Anggota');
            }

            if ($oldUser) {
                $this->accessRevocation->revokeMemberAppTokens($oldUser, $reasonCode, $actor, $lockedMember);
            }

            $this->audit->log('member.account.unlinked', 'cooperative.member', $lockedMember, [
                'old' => [
                    'user_id' => $oldUserId,
                    'organization_id' => $lockedMember->organization_id,
                ],
                'new' => [
                    'user_id' => null,
                    'organization_id' => $lockedMember->organization_id,
                    'reason_code' => $reasonCode,
                    'reason_supplied' => true,
                ],
                'reason' => 'Controlled member account-unlink reason code.',
            ]);

            return $lockedMember->refresh();
        });
    }

    private function assertActorCanLink(User $actor, CooperativeMember $member): void
    {
        if (! $actor->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)) {
            throw new AuthorizationException('You are not authorized to manage member accounts.');
        }

        $this->scope->assertVisible($actor, $member);
    }

    private function assertTargetEligible(CooperativeMember $member, User $target): void
    {
        if ($target->organization_id === null || (string) $target->organization_id !== (string) $member->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => 'User yang ditautkan harus berada dalam organisasi yang sama.',
            ]);
        }

        if ($target->email_verified_at === null) {
            throw ValidationException::withMessages([
                'user_id' => 'Email user harus terverifikasi sebelum ditautkan.',
            ]);
        }

        if ($target->cooperativeMember()->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'User ini sudah tertaut dengan anggota koperasi lain.',
            ]);
        }

        if ($this->isPrivileged($target)) {
            throw ValidationException::withMessages([
                'user_id' => 'Akun berprivilege atau operasional tidak dapat ditautkan sebagai anggota koperasi.',
            ]);
        }
    }

    private function isPrivileged(User $user): bool
    {
        if ($user->hasAnyRole([
            'System Admin',
            'Admin Pusat',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
            'Finance Pusat',
            'Finance Unit',
            'HR Pusat',
            'HR Unit',
            'Employee',
            'Technician',
        ])) {
            return true;
        }

        foreach ([
            PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
            PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::EMPLOYEE_VIEW_ALL->value,
            PermissionEnum::PAYROLL_PROCESS->value,
            PermissionEnum::PAYROLL_APPROVE->value,
        ] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function reasonCode(string $reason): string
    {
        return in_array($reason, self::REASON_CODES, true) ? $reason : 'other';
    }
}
