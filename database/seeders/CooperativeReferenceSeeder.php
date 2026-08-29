<?php

namespace Database\Seeders;

use App\Models\CooperativeContributionType;
use App\Models\Organization;
use App\Models\PosCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CooperativeReferenceSeeder extends Seeder
{
    /**
     * Seed production-safe reference and master data for cooperative operations.
     * Uses firstOrCreate to ensure existing operator-configured settings (e.g. default_amount, is_active)
     * are preserved and never silently overwritten during deployments.
     */
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Jaya Bersama No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasijayabersama.id',
                'is_active' => true,
            ],
        );

        $contributionTypes = [
            ['code' => 'POKOK', 'name' => 'Simpanan Pokok', 'category' => 'POKOK', 'default_amount' => 200000, 'frequency' => 'ONCE', 'is_active' => true],
            ['code' => 'WAJIB', 'name' => 'Simpanan Wajib', 'category' => 'WAJIB', 'default_amount' => 100000, 'frequency' => 'MONTHLY', 'is_active' => true],
            ['code' => 'SUKARELA', 'name' => 'Simpanan Sukarela', 'category' => 'SUKARELA', 'default_amount' => 0, 'frequency' => 'ADHOC', 'is_active' => true],
            ['code' => 'KHUSUS', 'name' => 'Simpanan Khusus', 'category' => 'KHUSUS', 'default_amount' => 0, 'frequency' => 'ADHOC', 'is_active' => true],
        ];

        foreach ($contributionTypes as $type) {
            CooperativeContributionType::query()->firstOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }

        $posCategories = [
            ['slug' => 'sembako', 'name' => 'Sembako'],
            ['slug' => 'minuman', 'name' => 'Minuman'],
            ['slug' => 'atk', 'name' => 'ATK & Kebutuhan Kantor'],
            ['slug' => 'espresso', 'name' => 'Espresso'],
            ['slug' => 'signature', 'name' => 'Signature'],
            ['slug' => 'non-coffee', 'name' => 'Non-Coffee'],
        ];

        foreach ($posCategories as $category) {
            PosCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'is_active' => true],
            );
        }
    }
}
