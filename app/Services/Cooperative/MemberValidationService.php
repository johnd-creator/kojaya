<?php

namespace App\Services\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MemberValidationService
{
    public const ACTION_VERIFIED = 'admin_verified';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REVISION = 'revision_requested';

    public const ACTION_REJECTED = 'rejected';

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function verifyByAdmin(CooperativeMember $member, User $validator, ?string $notes = null): CooperativeMember
    {
        return DB::transaction(function () use ($member, $validator, $notes): CooperativeMember {
            $member->forceFill([
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'admin_validated_at' => Carbon::now(),
                'admin_validated_by' => $validator->id,
                'admin_validation_notes' => $notes,
            ])->save();

            $member->user?->removeRole('Anggota');

            $this->logValidation($member, $validator, self::ACTION_VERIFIED, $notes);

            return $member->refresh();
        });
    }

    public function approveFinal(CooperativeMember $member, User $validator, ?string $notes = null): CooperativeMember
    {
        return DB::transaction(function () use ($member, $validator, $notes): CooperativeMember {
            $member->forceFill([
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ])->save();

            if ($member->user && ! $member->user->hasRole('Anggota')) {
                $role = Role::query()->firstOrCreate(['name' => 'Anggota']);
                $member->user->assignRole($role);
            }

            $this->logValidation($member, $validator, self::ACTION_APPROVED, $notes);

            return $member->refresh();
        });
    }

    public function requestRevision(CooperativeMember $member, User $validator, string $notes): CooperativeMember
    {
        return DB::transaction(function () use ($member, $validator, $notes): CooperativeMember {
            $member->forceFill([
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ])->save();

            $member->user?->removeRole('Anggota');

            $this->logValidation($member, $validator, self::ACTION_REVISION, $notes);

            return $member->refresh();
        });
    }

    public function reject(CooperativeMember $member, User $validator, string $notes): CooperativeMember
    {
        return DB::transaction(function () use ($member, $validator, $notes): CooperativeMember {
            $member->forceFill([
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ])->save();

            $member->user?->removeRole('Anggota');

            $this->logValidation($member, $validator, self::ACTION_REJECTED, $notes);

            return $member->refresh();
        });
    }

    public function isPendingReview(CooperativeMember $member): bool
    {
        return in_array($member->validation_status, [
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW,
        ], true);
    }

    public function canBeVerifiedByAdmin(CooperativeMember $member): bool
    {
        return in_array($member->validation_status, [
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_REVISION,
        ], true);
    }

    public function canBeApprovedFinal(CooperativeMember $member): bool
    {
        return $member->validation_status === CooperativeMember::VALIDATION_PENDING_REVIEW;
    }

    private function logValidation(CooperativeMember $member, User $validator, string $action, ?string $notes): void
    {
        try {
            AuditLog::create([
                'user_id' => $validator->id,
                'action' => 'sso.member_validation.'.$action,
                'module' => 'cooperative.sso',
                'subject_type' => CooperativeMember::class,
                'subject_id' => $member->id,
                'old_values' => null,
                'new_values' => [
                    'validation_status' => $member->validation_status,
                    'notes' => $notes,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            $this->audit->log('sso.member_validation.audit_failed', 'cooperative.sso', $member, [
                'new' => ['action' => $action, 'error' => $exception->getMessage()],
            ]);
        }
    }
}
