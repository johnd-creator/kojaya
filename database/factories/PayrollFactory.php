<?php

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();
        $basicSalary = fake()->randomFloat(2, 3000000, 15000000);
        $allowance = fake()->randomFloat(2, 0, 5000000);
        $deduction = fake()->randomFloat(2, 0, 2000000);
        $tax = fake()->randomFloat(2, 0, 1000000);
        $bpjs = fake()->randomFloat(2, 0, 750000);
        $netSalary = max(0, $basicSalary + $allowance - $deduction - $tax - $bpjs);

        return [
            'employee_id' => Employee::factory()->state(['organization_id' => $organization]),
            'organization_id' => $organization,
            'period' => now()->format('Y-m'),
            'basic_salary' => $basicSalary,
            'total_allowance' => $allowance,
            'total_deduction' => $deduction,
            'tax_amount' => $tax,
            'bpjs_amount' => $bpjs,
            'net_salary' => $netSalary,
            'status' => fake()->randomElement([
                PayrollStatus::Draft->value,
                PayrollStatus::Processed->value,
                PayrollStatus::Paid->value,
            ]),
            'pph21_calculation_breakdown' => ['gross' => $basicSalary + $allowance],
            'bpjs_calculation_breakdown' => ['total' => $bpjs],
        ];
    }
}
