<?php

namespace Database\Seeders;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class CooperativeMemberLifecycleSeeder extends Seeder
{
    /**
     * Run non-production deterministic member lifecycle dataset seeding.
     * Strictly restricted to local development, testing, and playwright environments.
     *
     * Enriches and reconciles the 7 canonical member personas (P06-P10, P12, P13)
     * with deterministic lifecycle metadata (admin verification, pengurus approval,
     * revision requests, and rejections).
     *
     * Invariants:
     * - Does NOT create new personas or members (expected total members = 7).
     * - P11 (BLOCKED_UNKNOWN), P14, and P15 are NOT created.
     * - Zero financial fixtures, zero store credit configuration, zero Sanctum tokens.
     */
    public function run(): void
    {
        if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
            throw new LogicException('CooperativeMemberLifecycleSeeder is only available in local, testing, or playwright environments.');
        }

        // 1. Ensure SEED-03 persona dependency is bootstrapped
        $this->call(CooperativePersonaSeeder::class);

        // 2. Lookup canonical verifier / approver actors
        $adminKop = User::query()->where('email', 'seed.admin.kop@kojaya.test')->first();
        if (! $adminKop) {
            throw new LogicException('Admin Koperasi persona (seed.admin.kop@kojaya.test) not found for lifecycle verification.');
        }

        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->first();
        if (! $pengurus) {
            throw new LogicException('Pengurus Koperasi persona (seed.pengurus@kojaya.test) not found for lifecycle approval.');
        }

        $fixedDate = '2026-06-01';
        $tSubmitted = '2026-06-01 08:00:00';
        $tAdminVerified = '2026-06-01 09:00:00';
        $tRevision = '2026-06-01 09:30:00';
        $tFinalDecision = '2026-06-01 10:00:00';

        // 3. Define lifecycle datasets for the 7 canonical synthetic members
        $lifecycleDatasets = [
            // P06: WAITING_VERIFICATION
            'DEV-KOP-006' => [
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
                'tanggal_aktif' => null,
                'onboarding_submitted_at' => null,
                'profile_completed_at' => null,
                'admin_validated_at' => null,
                'admin_validated_by' => null,
                'admin_validation_notes' => null,
                'validated_at' => null,
                'validated_by' => null,
                'validation_notes' => null,
            ],
            // P07: UNDER_REVIEW (Admin verified, awaiting final Pengurus approval)
            'DEV-KOP-007' => [
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'tanggal_aktif' => null,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => null,
                'admin_validated_at' => $tAdminVerified,
                'admin_validated_by' => $adminKop->id,
                'admin_validation_notes' => 'Dokumen dan identitas anggota diverifikasi oleh Admin Koperasi. Menunggu persetujuan Pengurus.',
                'validated_at' => null,
                'validated_by' => null,
                'validation_notes' => null,
            ],
            // P08: REVISION_REQUIRED (Revision requested by Admin / Pengurus)
            'DEV-KOP-008' => [
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
                'tanggal_aktif' => null,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => null,
                'admin_validated_at' => null,
                'admin_validated_by' => null,
                'admin_validation_notes' => null,
                'validated_at' => $tRevision,
                'validated_by' => $adminKop->id,
                'validation_notes' => 'Perbarui nomor telepon dan alamat domisili sebelum mengirim ulang data pendaftaran.',
            ],
            // P09: REJECTED (Rejected by Pengurus)
            'DEV-KOP-009' => [
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
                'tanggal_aktif' => null,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => null,
                'admin_validated_at' => null,
                'admin_validated_by' => null,
                'admin_validation_notes' => null,
                'validated_at' => $tFinalDecision,
                'validated_by' => $pengurus->id,
                'validation_notes' => 'Pendaftaran tidak memenuhi persyaratan keanggotaan koperasi.',
            ],
            // P10: ACTIVE (Completed Admin verification + Pengurus final approval)
            'DEV-KOP-010' => [
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'tanggal_aktif' => $fixedDate,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => $tSubmitted,
                'admin_validated_at' => $tAdminVerified,
                'admin_validated_by' => $adminKop->id,
                'admin_validation_notes' => 'Persona aktif diverifikasi admin koperasi.',
                'validated_at' => $tFinalDecision,
                'validated_by' => $pengurus->id,
                'validation_notes' => 'Persona aktif disetujui pengurus koperasi.',
            ],
            // P12: ACTIVE + Google SSO linked
            'DEV-KOP-012' => [
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'tanggal_aktif' => $fixedDate,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => $tSubmitted,
                'admin_validated_at' => $tAdminVerified,
                'admin_validated_by' => $adminKop->id,
                'admin_validation_notes' => 'Persona aktif diverifikasi admin koperasi.',
                'validated_at' => $tFinalDecision,
                'validated_by' => $pengurus->id,
                'validation_notes' => 'Persona aktif disetujui pengurus koperasi.',
            ],
            // P13: ACTIVE (Standard credentials)
            'DEV-KOP-013' => [
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'tanggal_aktif' => $fixedDate,
                'onboarding_submitted_at' => $tSubmitted,
                'profile_completed_at' => $tSubmitted,
                'admin_validated_at' => $tAdminVerified,
                'admin_validated_by' => $adminKop->id,
                'admin_validation_notes' => 'Persona aktif diverifikasi admin koperasi.',
                'validated_at' => $tFinalDecision,
                'validated_by' => $pengurus->id,
                'validation_notes' => 'Persona aktif disetujui pengurus koperasi.',
            ],
        ];

        // 4. Apply lifecycle metadata to each canonical member
        foreach ($lifecycleDatasets as $memberNo => $attributes) {
            $member = CooperativeMember::query()->where('member_no', $memberNo)->first();
            if (! $member) {
                throw new LogicException("Canonical member {$memberNo} not found after persona seeding.");
            }

            $member->forceFill($attributes)->save();
        }
    }
}
