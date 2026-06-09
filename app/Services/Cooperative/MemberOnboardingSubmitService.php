<?php

namespace App\Services\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberOnboardingSubmitService
{
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
                'jenis_anggota' => $data['jenis_anggota'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'kategori' => $data['kategori'],
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'pekerjaan' => $data['pekerjaan'] ?? null,
                'perusahaan' => $data['perusahaan'] ?? null,
                'no_rekening' => $data['no_rekening'] ?? null,
                'nama_bank' => $data['nama_bank'] ?? null,
                'nama_pemilik_rekening' => $data['nama_pemilik_rekening'] ?? null,
                'profile_completed_at' => Carbon::now(),
                'onboarding_submitted_at' => Carbon::now(),
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'status' => CooperativeMember::VALIDATION_PENDING,
            ])->save();

            $user = $member->user;
            if ($user && $user->email !== $data['email']) {
                $user->forceFill(['email' => $data['email']])->save();
            }

            $this->writeAuditLog($member, $actor);

            return $member->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function missingRequiredFields(array $data): array
    {
        $required = ['name', 'email', 'phone', 'address', 'identity_number', 'jenis_anggota', 'jenis_kelamin', 'kategori'];
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
            AuditLog::create([
                'user_id' => $actor?->id ?? $member->user_id,
                'action' => 'sso.member_onboarding.submitted',
                'module' => 'cooperative.sso',
                'subject_type' => CooperativeMember::class,
                'subject_id' => $member->id,
                'old_values' => null,
                'new_values' => [
                    'validation_status' => $member->validation_status,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable) {
            // audit log best-effort, never break onboarding
        }
    }
}
