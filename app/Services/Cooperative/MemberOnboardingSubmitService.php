<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberOnboardingSubmitService
{
    public function __construct(
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(CooperativeMember $member, array $data, ?User $actor = null): CooperativeMember
    {
        return DB::transaction(function () use ($member, $data, $actor): CooperativeMember {
            // Narrow explicitly safe profile allowlist ONLY (R1-01):
            // Must NOT edit: identity_number (NIK), kategori/company, organization,
            // employee, member_no, membership_type, npwp, bank fields, or users.email.
            $safeUpdates = [];

            if (isset($data['name']) && is_string($data['name']) && trim($data['name']) !== '') {
                $safeUpdates['name'] = trim($data['name']);
                $safeUpdates['nama_anggota'] = trim($data['name']);
            }

            if (isset($data['phone']) && is_string($data['phone'])) {
                $safeUpdates['phone'] = trim($data['phone']);
                $safeUpdates['no_telp'] = trim($data['phone']);
            }

            if (isset($data['address']) && is_string($data['address'])) {
                $safeUpdates['address'] = trim($data['address']);
            }

            if (isset($data['jenis_kelamin']) && in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
                $safeUpdates['jenis_kelamin'] = $data['jenis_kelamin'];
            }

            if (isset($data['tanggal_lahir'])) {
                $safeUpdates['tanggal_lahir'] = $data['tanggal_lahir'];
            }

            if (isset($data['tempat_lahir']) && is_string($data['tempat_lahir'])) {
                $safeUpdates['tempat_lahir'] = trim($data['tempat_lahir']);
            }

            if (isset($data['pekerjaan']) && is_string($data['pekerjaan'])) {
                $safeUpdates['pekerjaan'] = trim($data['pekerjaan']);
            }

            $safeUpdates['profile_completed_at'] = Carbon::now();
            $safeUpdates['onboarding_submitted_at'] = Carbon::now();

            $member->forceFill($safeUpdates)->save();

            $this->writeAuditLog($member, $actor);

            DB::afterCommit(fn () => $this->notificationDispatcher->memberSubmittedForValidation($member->refresh(), $actor));

            return $member->refresh();
        });
    }

    private function writeAuditLog(CooperativeMember $member, ?User $actor): void
    {
        try {
            $this->audit->log('sso.member_onboarding.submitted', 'cooperative.sso', $member, [
                'new' => [
                    'validation_status' => $member->validation_status,
                ],
                'reason' => 'Member onboarding submitted for validation.',
            ]);
        } catch (\Throwable) {
            // audit log best-effort, never break onboarding
        }
    }
}
