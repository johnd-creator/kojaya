<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberValidationService
{
    public const ACTION_VERIFIED = 'admin_verified';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REVISION = 'revision_requested';

    public const ACTION_REJECTED = 'rejected';

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
        private readonly MemberStatusTransitionService $transitions,
    ) {}

    public function verifyByAdmin(CooperativeMember $member, User $validator, ?string $notes = null): CooperativeMember
    {
        $member = $this->transitions->verifyByAdmin(
            $member,
            $validator,
            $notes,
            [
                'admin_validated_at' => Carbon::now(),
                'admin_validated_by' => $validator->id,
                'admin_validation_notes' => $notes,
            ],
        );
        DB::afterCommit(fn () => $this->notificationDispatcher->memberAdminVerified($member, $validator));

        return $member;
    }

    public function approveFinal(CooperativeMember $member, User $validator, ?string $notes = null): CooperativeMember
    {
        $this->assertApproverIsNotVerifier($member, $validator);

        $member = $this->transitions->approveFinal(
            $member,
            $validator,
            $notes,
            [
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ],
        );
        DB::afterCommit(fn () => $this->notificationDispatcher->memberFinalApproved($member, $validator));

        return $member;
    }

    public function requestRevision(CooperativeMember $member, User $validator, string $notes): CooperativeMember
    {
        $member = $this->transitions->requestRevision(
            $member,
            $validator,
            $notes,
            [
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ],
        );
        DB::afterCommit(fn () => $this->notificationDispatcher->memberRevisionRequested($member, $validator, $notes));

        return $member;
    }

    public function reject(CooperativeMember $member, User $validator, string $notes): CooperativeMember
    {
        $member = $this->transitions->reject(
            $member,
            $validator,
            $notes,
            [
                'validated_at' => Carbon::now(),
                'validated_by' => $validator->id,
                'validation_notes' => $notes,
            ],
        );
        DB::afterCommit(fn () => $this->notificationDispatcher->memberRejected($member, $validator, $notes));

        return $member;
    }

    public function isPendingReview(CooperativeMember $member): bool
    {
        return $member->validation_status === CooperativeMember::VALIDATION_PENDING_REVIEW;
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

    /**
     * Maker-checker: the admin who verified the member cannot be the same
     * person who performs the final approval.
     */
    private function assertApproverIsNotVerifier(CooperativeMember $member, User $approver): void
    {
        if ($member->admin_validated_by !== null && (string) $member->admin_validated_by === (string) $approver->id) {
            throw ValidationException::withMessages([
                'approved_by' => 'Verifier administrasi tidak boleh menjadi approver final.',
            ]);
        }
    }
}
