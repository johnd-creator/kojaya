<?php

namespace Database\Seeders;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;
use Spatie\Permission\Models\Role;

class CooperativePersonaSeeder extends Seeder
{
    /**
     * Run non-production deterministic persona seeding.
     * Strictly restricted to local development, testing, and playwright environments.
     * Creates:
     * - 12 valid baseline User personas (P01-P05 Staff/Admin, P06-P10 & P12-P13 Members)
     * - 7 valid CooperativeMember personas (P06-P10 & P12-P13)
     * - 1 deterministic Google SocialAccount (P12)
     *
     * Invariant: All personas belong to KOP-001 (Cooperative Legal Entity).
     * Zero CooperativeMember fixtures are attached to KBU-001 or ISO-999.
     * P11 (BLOCKED_UNKNOWN), P14, and P15 are NOT created.
     */
    public function run(): void
    {
        if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
            throw new LogicException('CooperativePersonaSeeder is only available in local, testing, or playwright environments.');
        }

        if (! Role::query()->where('name', 'Anggota')->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        $kop = Organization::query()->where('code', 'KOP-001')->first();
        if (! $kop || ! Organization::query()->where('code', 'KBU-001')->exists()) {
            $this->call(CooperativeFixtureReferenceSeeder::class);
            $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        }

        $fixedDate = '2026-06-01';
        $fixedTimestamp = '2026-06-01 08:00:00';

        // 1. Staff and Operational Personas (P01 - P05: User = YES, Role = YES, Member = NO)
        $staffPersonas = [
            [
                'key' => 'P01',
                'name' => 'Seed System Admin',
                'email' => 'seed.system.admin@kojaya.test',
                'role' => 'System Admin',
            ],
            [
                'key' => 'P02',
                'name' => 'Seed Pengurus Koperasi',
                'email' => 'seed.pengurus@kojaya.test',
                'role' => 'Pengurus Koperasi',
            ],
            [
                'key' => 'P03',
                'name' => 'Seed Manajer Koperasi',
                'email' => 'seed.manajer@kojaya.test',
                'role' => 'Manajer Koperasi',
            ],
            [
                'key' => 'P04',
                'name' => 'Seed Admin Koperasi',
                'email' => 'seed.admin.kop@kojaya.test',
                'role' => 'Admin Koperasi',
            ],
            [
                'key' => 'P05',
                'name' => 'Seed Kasir Koperasi',
                'email' => 'seed.kasir@kojaya.test',
                'role' => 'Kasir Koperasi',
            ],
        ];

        $users = [];

        foreach ($staffPersonas as $persona) {
            $user = User::query()->where('email', $persona['email'])->first();
            if (! $user) {
                $user = new User;
                $user->email = $persona['email'];
            }
            $user->name = $persona['name'];
            $user->password = 'password';
            $user->organization_id = $kop->id;
            $user->email_verified_at = $fixedTimestamp;
            $user->save();

            $user->syncRoles([$persona['role']]);
            $users[$persona['key']] = $user;

            // Ensure no CooperativeMember is attached to administrative / operational staff
            CooperativeMember::query()->where('user_id', $user->id)->delete();
        }

        $pengurusUser = $users['P02'];
        $adminKopUser = $users['P04'];

        // 2. Member Personas (P06 - P10, P12, P13: User = YES, Role = Anggota, Member = YES)
        $memberPersonas = [
            [
                'key' => 'P06',
                'name' => 'Seed Member Waiting',
                'email' => 'seed.member.waiting@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-006',
                'nik' => '3174990000000006',
                'phone' => '081299990006',
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
                'is_active_member' => false,
            ],
            [
                'key' => 'P07',
                'name' => 'Seed Member Review',
                'email' => 'seed.member.review@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-007',
                'nik' => '3174990000000007',
                'phone' => '081299990007',
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'is_active_member' => false,
            ],
            [
                'key' => 'P08',
                'name' => 'Seed Member Revision',
                'email' => 'seed.member.revision@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-008',
                'nik' => '3174990000000008',
                'phone' => '081299990008',
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
                'is_active_member' => false,
            ],
            [
                'key' => 'P09',
                'name' => 'Seed Member Rejected',
                'email' => 'seed.member.rejected@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-009',
                'nik' => '3174990000000009',
                'phone' => '081299990009',
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
                'is_active_member' => false,
            ],
            [
                'key' => 'P10',
                'name' => 'Seed Member Active',
                'email' => 'seed.member.active@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-010',
                'nik' => '3174990000000010',
                'phone' => '081299990010',
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'is_active_member' => true,
            ],
            [
                'key' => 'P12',
                'name' => 'Seed Member Google',
                'email' => 'seed.member.google@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-012',
                'nik' => '3174990000000012',
                'phone' => '081299990012',
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'is_active_member' => true,
                'google_linked' => true,
            ],
            [
                'key' => 'P13',
                'name' => 'Seed Member No Google',
                'email' => 'seed.member.no-google@kojaya.test',
                'role' => 'Anggota',
                'member_no' => 'DEV-KOP-013',
                'nik' => '3174990000000013',
                'phone' => '081299990013',
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'is_active_member' => true,
                'google_linked' => false,
            ],
        ];

        foreach ($memberPersonas as $persona) {
            $user = User::query()->where('email', $persona['email'])->first();
            if (! $user) {
                $user = new User;
                $user->email = $persona['email'];
            }
            $user->name = $persona['name'];
            $user->password = 'password';
            $user->organization_id = $kop->id;
            $user->email_verified_at = $fixedTimestamp;
            $user->save();

            $user->syncRoles([$persona['role']]);
            $users[$persona['key']] = $user;

            $member = CooperativeMember::withTrashed()
                ->where('member_no', $persona['member_no'])
                ->orWhere('no_anggota', $persona['member_no'])
                ->first();

            if (! $member) {
                $member = new CooperativeMember;
            } elseif ($member->trashed()) {
                $member->restore();
            }

            $member->organization_id = $kop->id;
            $member->user_id = $user->id;
            $member->member_no = $persona['member_no'];
            $member->no_anggota = $persona['member_no'];
            $member->name = $persona['name'];
            $member->nama_anggota = $persona['name'];
            $member->email = $persona['email'];
            $member->phone = $persona['phone'];
            $member->no_telp = $persona['phone'];
            $member->identity_number = $persona['nik'];
            $member->address = 'Jl. Uji Coba Koperasi No. '.substr($persona['member_no'], -3).', Jakarta';
            $member->jenis_anggota = 'AB';
            $member->jenis_kelamin = 'L';
            $member->kategori = 'IP';
            $member->autodebet = 'MANUAL';
            $member->joined_at = $fixedDate;
            $member->status = $persona['status'];
            $member->validation_status = $persona['validation_status'];

            if ($persona['is_active_member']) {
                $member->tanggal_aktif = $fixedDate;
                $member->validated_at = $fixedTimestamp;
                $member->validated_by = $pengurusUser->id;
                $member->validation_notes = 'Persona aktif disetujui pengurus koperasi.';
                $member->admin_validated_at = $fixedTimestamp;
                $member->admin_validated_by = $adminKopUser->id;
                $member->admin_validation_notes = 'Persona aktif diverifikasi admin koperasi.';
                $member->profile_completed_at = $fixedTimestamp;
                $member->onboarding_submitted_at = $fixedTimestamp;
                $member->credit_limit = 500000;
                $member->credit_term_days = 30;
            } else {
                $member->tanggal_aktif = null;
                $member->validated_at = null;
                $member->validated_by = null;
                $member->validation_notes = null;
                $member->admin_validated_at = null;
                $member->admin_validated_by = null;
                $member->admin_validation_notes = null;
                $member->profile_completed_at = null;
                $member->onboarding_submitted_at = match ($persona['key']) {
                    'P07', 'P08' => $fixedTimestamp,
                    default => null,
                };
            }

            if (! empty($persona['google_linked'])) {
                $member->sso_provider = 'google';
                $member->last_sso_login_at = null;
            } else {
                $member->sso_provider = null;
                $member->last_sso_login_at = null;
            }

            $member->save();

            // Handle SocialAccount for Google linking
            if (! empty($persona['google_linked'])) {
                SocialAccount::query()->updateOrCreate(
                    [
                        'provider' => 'google',
                        'provider_id' => 'google-seed-sub-012',
                    ],
                    [
                        'user_id' => $user->id,
                        'provider_email' => $persona['email'],
                        'provider_name' => $persona['name'],
                        'provider_avatar' => 'https://lh3.googleusercontent.com/a/seed-member-google',
                        'token_type' => null,
                        'expires_in' => null,
                        'access_token' => null,
                        'refresh_token' => null,
                        'linked_at' => $fixedTimestamp,
                        'last_login_at' => null,
                    ],
                );
            } else {
                SocialAccount::query()->where('user_id', $user->id)->delete();
            }
        }
    }
}
