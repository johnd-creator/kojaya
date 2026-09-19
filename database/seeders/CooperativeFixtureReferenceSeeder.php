<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use LogicException;

class CooperativeFixtureReferenceSeeder extends Seeder
{
    /**
     * Run non-production reference and organization fixture seeding.
     * Strictly restricted to local development, testing, and playwright environments.
     * Establishes the deterministic multi-tenant topology (KOP-001 -> KBU-001, and ISO-999).
     */
    public function run(): void
    {
        if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
            throw new LogicException('CooperativeFixtureReferenceSeeder is only available in local, testing, or playwright environments.');
        }

        $kop = Organization::query()->where('code', 'KOP-001')->first();
        if (! $kop) {
            $this->call(CooperativeReferenceSeeder::class);
            $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        }

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
