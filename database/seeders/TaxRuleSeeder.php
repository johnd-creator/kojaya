<?php

namespace Database\Seeders;

use App\Models\TaxRule;
use Illuminate\Database\Seeder;

class TaxRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rule = TaxRule::defaultPph21Ter2024();

        TaxRule::query()->updateOrCreate(
            ['code' => $rule['code']],
            $rule
        );
    }
}
