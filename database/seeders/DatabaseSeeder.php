<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TaxRuleSeeder::class,
            RolePermissionSeeder::class,
            CooperativeSeeder::class,
            AnggotaSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                DemoDataSeeder::class,
            ]);
        }
    }
}
