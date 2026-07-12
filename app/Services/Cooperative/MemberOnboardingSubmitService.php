<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $missing = $this->missingRequiredFields($data);

        if (count($missing) > 0) {
            throw ValidationException::withMessages([
                'form' => 'Lengkapi field wajib: '.implode(', ', $missing).'.',
            ]);
        }

        return DB::transaction(function () use ($member, $data, $actor): CooperativeMember {
            $member->forceFill([
                'name' => $data['name'],
                'nama_anggota' => $data['name'],
                'phone' => $data['phone'],
                'no_telp' => $data['phone'],
                'address' => $data['address'],
                'identity_number' => $data['identity_number'],
                'npwp' => $data['npwp'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'],
                'kategori' => $data['kategori'],
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'pekerjaan' => $data['pekerjaan'] ?? null,
                'no_rekening' => $data['no_rekening'] ?? null,
                'nama_bank' => $data['nama_bank'] ?? null,
                'nama_pemilik_rekening' => $data['nama_pemilik_rekening'] ?? null,
                'profile_completed_at' => Carbon::now(),
                'onboarding_submitted_at' => Carbon::now(),
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'status' => $member->status === CooperativeMember::VALIDATION_ACTIVE
                    ? CooperativeMember::VALIDATION_ACTIVE
                    : CooperativeMember::VALIDATION_PENDING,
            ])->save();

            $user = $member->user;
            if ($user && $user->email !== $data['email']) {
                $user->forceFill(['email' => $data['email']])->save();
            }

            $this->writeAuditLog($member, $actor);

            DB::afterCommit(fn () => $this->notificationDispatcher->memberSubmittedForValidation($member->refresh(), $actor));

            return $member->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function missingRequiredFields(array $data): array
    {
        $required = ['name', 'email', 'phone', 'address', 'identity_number', 'jenis_kelamin', 'kategori'];
        $missing = [];
        foreach ($required as $key) {
            $value = $data[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
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
