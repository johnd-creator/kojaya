<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberOnboardingProgress;

class MemberOnboardingService
{
    /**
     * @return array<string, mixed>
     */
    public function status(CooperativeMember $member): array
    {
        $progress = $this->progressFor($member);
        $profileComplete = $this->profileIsComplete($member);
        $firstSavingsPaid = $member->payments()->where('status', 'APPROVED')->exists()
            || $member->ledgerEntries()->where('credit', '>', 0)->exists();

        $steps = [
            [
                'key' => 'profile',
                'label' => 'Lengkapi profil',
                'description' => 'Pastikan kontak, identitas, dan alamat sudah benar.',
                'href' => route('member.profile', absolute: false),
                'completed' => $profileComplete,
                'completed_at' => $progress->profile_completed_at?->toIso8601String(),
            ],
            [
                'key' => 'first_savings',
                'label' => 'Setoran simpanan pertama',
                'description' => 'Lihat tagihan dan riwayat pembayaran simpanan.',
                'href' => route('member.savings', absolute: false),
                'completed' => $firstSavingsPaid,
                'completed_at' => $progress->first_savings_paid_at?->toIso8601String(),
            ],
            [
                'key' => 'loans',
                'label' => 'Kenali pengajuan pinjaman',
                'description' => 'Cek simulasi dan status pengajuan pinjaman.',
                'href' => route('member.loans', absolute: false),
                'completed' => $progress->loan_intro_seen_at !== null,
                'completed_at' => $progress->loan_intro_seen_at?->toIso8601String(),
            ],
            [
                'key' => 'rewards',
                'label' => 'Aktifkan benefit poin',
                'description' => 'Pantau poin dan katalog reward anggota.',
                'href' => route('member.rewards', absolute: false),
                'completed' => $progress->reward_intro_seen_at !== null,
                'completed_at' => $progress->reward_intro_seen_at?->toIso8601String(),
            ],
        ];

        $completedCount = collect($steps)->where('completed', true)->count();
        $isComplete = $completedCount === count($steps);

        if ($isComplete && $progress->completed_at === null) {
            $progress->update(['completed_at' => now()]);
        }

        return [
            'member_id' => $member->id,
            'completed_steps' => $completedCount,
            'total_steps' => count($steps),
            'progress_percent' => (int) round(($completedCount / count($steps)) * 100),
            'is_complete' => $isComplete,
            'is_dismissed' => $progress->dismissed_at !== null,
            'steps' => $steps,
        ];
    }

    public function markStep(CooperativeMember $member, string $step): MemberOnboardingProgress
    {
        $column = match ($step) {
            'profile' => 'profile_completed_at',
            'first_savings' => 'first_savings_paid_at',
            'loans' => 'loan_intro_seen_at',
            'rewards' => 'reward_intro_seen_at',
            default => null,
        };

        abort_if($column === null, 422, 'Langkah onboarding tidak dikenal.');

        $progress = $this->progressFor($member);
        $progress->update([$column => $progress->{$column} ?? now()]);

        return $progress->refresh();
    }

    public function dismiss(CooperativeMember $member): MemberOnboardingProgress
    {
        $progress = $this->progressFor($member);
        $progress->update(['dismissed_at' => now()]);

        return $progress->refresh();
    }

    public function progressFor(CooperativeMember $member): MemberOnboardingProgress
    {
        return MemberOnboardingProgress::query()->firstOrCreate([
            'cooperative_member_id' => $member->id,
        ]);
    }

    private function profileIsComplete(CooperativeMember $member): bool
    {
        return filled($member->name)
            && filled($member->email)
            && filled($member->phone)
            && filled($member->identity_number)
            && filled($member->address)
            && filled($member->jenis_anggota)
            && filled($member->jenis_kelamin)
            && filled($member->kategori)
            && $member->onboarding_submitted_at !== null;
    }
}
