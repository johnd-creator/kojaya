<?php

namespace Database\Seeders;

use App\Models\LoanType;
use Illuminate\Database\Seeder;

class LoanTypeSeeder extends Seeder
{
    /**
     * Seed canonical loan products with initial defaults.
     * Uses firstOrCreate so existing operator-managed loan parameters (interest rate,
     * min/max amounts, terms, active status) are strictly preserved during deployments.
     */
    public function run(): void
    {
        $loanTypes = [
            [
                'code' => 'emergency',
                'name' => 'Darurat',
                'description' => 'Untuk kebutuhan mendesak, proses persetujuan cepat.',
                'interest_rate' => 1.00,
                'admin_fee' => 0,
                'late_fee_per_day' => 0,
                'min_amount' => 500000,
                'max_amount' => 5000000,
                'min_term_months' => 1,
                'max_term_months' => 6,
            ],
            [
                'code' => 'productive',
                'name' => 'Produktif',
                'description' => 'Modal usaha untuk mengembangkan bisnis atau pertanian.',
                'interest_rate' => 1.25,
                'admin_fee' => 0,
                'late_fee_per_day' => 0,
                'min_amount' => 1000000,
                'max_amount' => 25000000,
                'min_term_months' => 3,
                'max_term_months' => 24,
            ],
            [
                'code' => 'consumer',
                'name' => 'Konsumtif',
                'description' => 'Untuk kebutuhan konsumsi pribadi atau rumah tangga.',
                'interest_rate' => 1.50,
                'admin_fee' => 0,
                'late_fee_per_day' => 0,
                'min_amount' => 1000000,
                'max_amount' => 15000000,
                'min_term_months' => 3,
                'max_term_months' => 18,
            ],
        ];

        foreach ($loanTypes as $loanType) {
            LoanType::query()->firstOrCreate(
                ['code' => $loanType['code']],
                [
                    ...$loanType,
                    'is_active' => true,
                ],
            );
        }
    }
}
