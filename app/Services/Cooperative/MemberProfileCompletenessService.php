<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberOnboardingProgress;
use Illuminate\Support\Carbon;

class MemberProfileCompletenessService
{
    /**
     * @var array<int, array{key: string, label: string, description: string}>
     */
    private const REQUIRED_FIELDS = [
        ['key' => 'name', 'label' => 'Nama Lengkap', 'description' => 'Sesuai identitas resmi.'],
        ['key' => 'email', 'label' => 'Email', 'description' => 'Email yang akan menerima notifikasi.'],
        ['key' => 'phone', 'label' => 'Nomor HP', 'description' => 'Untuk notifikasi dan validasi admin.'],
        ['key' => 'address', 'label' => 'Alamat Domisili', 'description' => 'Alamat terkini untuk surat menyurat.'],
        ['key' => 'identity_number', 'label' => 'Nomor Identitas', 'description' => 'NIK atau nomor identitas resmi lain.'],
        ['key' => 'jenis_anggota', 'label' => 'Jenis Anggota', 'description' => 'Biasa atau Luar Biasa.'],
        ['key' => 'kategori', 'label' => 'Kategori', 'description' => 'Kategori keanggotaan sesuai perusahaan/unit.'],
    ];

    /**
     * @return array{
     *     progress_percent: int,
     *     completed_fields: int,
     *     total_fields: int,
     *     is_complete: bool,
     *     missing: array<int, array{key: string, label: string, description: string}>,
     *     required_fields: array<int, array{key: string, label: string, description: string}>,
     *     login: array{email_verified: bool, google_linked: bool, provider_email: ?string, provider_name: ?string, last_login_at: ?string}
     * }
     */
    public function summarize(CooperativeMember $member): array
    {
        $missing = [];
        $completed = 0;

        foreach (self::REQUIRED_FIELDS as $field) {
            if (filled($member->{$field['key']})) {
                $completed++;
            } else {
                $missing[] = $field;
            }
        }

        $total = count(self::REQUIRED_FIELDS);
        $percent = (int) round(($completed / max($total, 1)) * 100);
        $isComplete = $completed === $total;

        $progress = MemberOnboardingProgress::query()->firstOrNew([
            'cooperative_member_id' => $member->id,
        ]);

        if ($isComplete && $progress->profile_completed_at === null) {
            $progress->profile_completed_at = Carbon::now();
            $progress->save();
        }

        $user = $member->user;
        $googleAccount = $user?->socialAccounts()->where('provider', 'google')->latest('last_login_at')->first();

        return [
            'progress_percent' => $percent,
            'completed_fields' => $completed,
            'total_fields' => $total,
            'is_complete' => $isComplete,
            'missing' => $missing,
            'required_fields' => self::REQUIRED_FIELDS,
            'login' => [
                'email_verified' => $user?->email_verified_at !== null,
                'google_linked' => $googleAccount !== null,
                'provider_email' => $googleAccount?->provider_email,
                'provider_name' => $googleAccount?->provider_name,
                'last_login_at' => $googleAccount?->last_login_at?->toIso8601String(),
            ],
        ];
    }
}
