<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CooperativeFixtureReferenceSeeder extends Seeder
{
    /**
     * Run non-production reference and organization fixture seeding.
     * Strictly restricted to local development, testing, and playwright environments.
     * Establishes the deterministic non-production topology:
     * - KOP-001: Cooperative legal entity (L0 HEAD_OFFICE). The ONLY organization allowed to own CooperativeMember records.
     * - KBU-001: PT subsidiary under cooperative ownership/control (L1 BRANCH). Owns Employee/workforce data, zero CooperativeMember.
     * - ISO-999: Synthetic isolated third-party organization (L0 HEAD_OFFICE) reserved for multi-tenant authorization testing.
     */
    public function run(): void
    {
        SeederEnvironmentGuard::assertAllowed(static::class);

        $kop = Organization::query()->where('code', 'KOP-001')->first();
        if (! $kop) {
            $this->call(CooperativeReferenceSeeder::class);
            $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        }

        // KBU-001 represents a commercial PT subsidiary (PT Koperasi Berkah Usaha).
        // It participates in the organizational hierarchy under KOP-001 but MUST NOT own CooperativeMember records.
        Organization::query()->firstOrCreate(
            ['code' => 'KBU-001'],
            [
                'id' => (string) Str::uuid(),
                'parent_id' => $kop->id,
                'name' => 'PT Koperasi Berkah Usaha',
                'level' => 'L1',
                'type' => 'BRANCH',
                'address' => 'Jl. Berkah Usaha No. 8, Jakarta',
                'phone' => '021-111111',
                'email' => 'operasional@koperasiberkahusaha.id',
                'is_active' => true,
                'latitude' => '-6.175392',
                'longitude' => '106.827153',
                'radius' => 150,
            ],
        );

        Organization::query()->firstOrCreate(
            ['code' => 'ISO-999'],
            [
                'id' => (string) Str::uuid(),
                'parent_id' => null,
                'name' => 'Koperasi Mandiri Sejahtera',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'address' => 'Jl. Mandiri Sejahtera No. 99, Surabaya',
                'phone' => '031-9999999',
                'email' => 'kontak@mandirisejahtera.id',
                'is_active' => true,
            ],
        );
    }
}
