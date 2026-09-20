<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\User;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SEED-06: Negative & Edge-Case Dataset Seeder.
 *
 * Safety Classification: TEST_ONLY_INVALID_FIXTURE.
 * Strict fail-closed guard: ONLY permitted in testing or playwright environments.
 * Deliberately excluded from DatabaseSeeder and production/staging/qa/local baselines.
 *
 * Responsible for canonical invalid persona P11 (BLOCKED_UNKNOWN).
 */
class CooperativeEdgeCaseFixtureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Strict Fail-Closed Environment Guard
        SeederEnvironmentGuard::assertAllowed(static::class);

        // 2. Bootstrap Prerequisites (roles, KOP-001 topology, baseline personas)
        $this->call([
            CooperativeMemberLifecycleSeeder::class,
        ]);

        // 3. Resolve Organization
        $headOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();

        // 4. Deterministic Canonical P11 Persona (BLOCKED_UNKNOWN)
        $this->seedP11BlockedPersona($headOffice);
    }

    private function seedP11BlockedPersona(Organization $headOffice): void
    {
        $email = 'seed.member.blocked@kojaya.test';
        $memberNo = 'DEV-KOP-011';

        // A. P11 User
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $user->update([
                'name' => 'Seed Member Blocked',
                'organization_id' => $headOffice->id,
                'password' => Hash::make('password'),
                'email_verified_at' => '2026-06-01 08:00:00',
            ]);
        } else {
            $user = User::query()->create([
                'name' => 'Seed Member Blocked',
                'email' => $email,
                'password' => Hash::make('password'),
                'organization_id' => $headOffice->id,
                'email_verified_at' => '2026-06-01 08:00:00',
            ]);
        }

        $user->syncRoles(['Anggota']);

        // B. P11 CooperativeMember (BLOCKED_UNKNOWN: status = INACTIVE, validation_status = INACTIVE)
        $member = CooperativeMember::withTrashed()
            ->where('member_no', $memberNo)
            ->orWhere('no_anggota', $memberNo)
            ->first();

        if (! $member) {
            $member = new CooperativeMember;
        } elseif ($member->trashed()) {
            $member->restore();
        }

        $member->organization_id = $headOffice->id;
        $member->user_id = $user->id;
        $member->member_no = $memberNo;
        $member->no_anggota = $memberNo;
        $member->name = 'Seed Member Blocked';
        $member->nama_anggota = 'Seed Member Blocked';
        $member->email = $email;
        $member->phone = '081299990011';
        $member->no_telp = '081299990011';
        $member->identity_number = '3174990000000011';
        $member->address = 'Jl. Uji Coba Koperasi No. 011, Jakarta';
        $member->jenis_anggota = 'AB';
        $member->jenis_kelamin = 'L';
        $member->kategori = 'IP';
        $member->autodebet = 'MANUAL';
        $member->joined_at = '2026-06-01';
        $member->status = CooperativeMember::VALIDATION_INACTIVE;
        $member->validation_status = CooperativeMember::VALIDATION_INACTIVE;
        $member->tanggal_aktif = null;
        $member->validated_at = null;
        $member->validated_by = null;
        $member->validation_notes = 'Persona diblokir atau status keanggotaan tidak valid.';
        $member->admin_validated_at = null;
        $member->admin_validated_by = null;
        $member->admin_validation_notes = null;
        $member->profile_completed_at = null;
        $member->onboarding_submitted_at = null;
        $member->sso_provider = null;
        $member->last_sso_login_at = null;
        $member->save();

        // C. Explicit Invariant: Guarantee Strictly Zero Financial Records for P11
        CooperativeDuesInvoice::query()->where('cooperative_member_id', $member->id)->delete();
        CooperativePayment::query()->where('cooperative_member_id', $member->id)->delete();
        CooperativeReceipt::query()->where('cooperative_member_id', $member->id)->delete();
        CooperativeLedgerEntry::query()->where('cooperative_member_id', $member->id)->delete();
        MemberStoreAccount::query()->where('cooperative_member_id', $member->id)->delete();
        Loan::query()->where('cooperative_member_id', $member->id)->delete();
        LoanPayment::query()->where('cooperative_member_id', $member->id)->delete();
        PosTransaction::query()->where('cooperative_member_id', $member->id)->delete();
    }
}
