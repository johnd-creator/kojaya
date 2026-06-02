<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\ThrEntitlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ThrEntitlementService
{
    public function calculateForEmployee(Employee $employee, int $year): ThrEntitlement
    {
        $cutoffDate = Carbon::create($year, 5, 31)->startOfDay();
        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date)->startOfDay() : $cutoffDate;
        $monthsWorked = $hireDate->greaterThan($cutoffDate)
            ? 0
            : min(12, (int) floor($hireDate->diffInMonths($cutoffDate)) + 1);
        $baseSalary = (float) ($employee->basic_salary ?? 0);
        $amount = round(($baseSalary / 12) * $monthsWorked, 2);

        return ThrEntitlement::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'year' => $year,
            ],
            [
                'organization_id' => $employee->organization_id,
                'months_worked' => $monthsWorked,
                'base_salary' => $baseSalary,
                'amount' => $amount,
                'status' => 'DRAFT',
                'calculated_at' => now(),
                'calculation_breakdown' => [
                    'cutoff_date' => $cutoffDate->toDateString(),
                    'hire_date' => $hireDate->toDateString(),
                    'formula' => '(base_salary / 12) * months_worked',
                ],
            ],
        );
    }

    /**
     * @return Collection<int, ThrEntitlement>
     */
    public function calculateForOrganization(string $organizationId, int $year): Collection
    {
        return Employee::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'ACTIVE')
            ->orderBy('employee_code')
            ->get()
            ->map(fn (Employee $employee): ThrEntitlement => $this->calculateForEmployee($employee, $year));
    }

    /**
     * @return array{total_employees:int,total_thr:float,breakdown:array<int,array{months:int,count:int}>}
     */
    public function previewOrganization(string $organizationId, int $year): array
    {
        $entitlements = $this->calculateForOrganization($organizationId, $year);
        $breakdown = $entitlements
            ->groupBy('months_worked')
            ->sortKeys()
            ->map(fn (Collection $group, int|string $months): array => [
                'months' => (int) $months,
                'count' => $group->count(),
            ])
            ->values()
            ->all();

        return [
            'total_employees' => $entitlements->count(),
            'total_thr' => round((float) $entitlements->sum(fn (ThrEntitlement $entitlement): float => (float) $entitlement->amount), 2),
            'breakdown' => $breakdown,
        ];
    }

    public function createPayrollFromEntitlement(ThrEntitlement $entitlement, string $period): Payroll
    {
        $payroll = Payroll::query()->create([
            'employee_id' => $entitlement->employee_id,
            'organization_id' => $entitlement->organization_id,
            'period' => $period,
            'basic_salary' => 0,
            'total_allowance' => $entitlement->amount,
            'total_deduction' => 0,
            'tax_amount' => 0,
            'bpjs_amount' => 0,
            'net_salary' => $entitlement->amount,
            'status' => 'DRAFT',
            'is_thr' => true,
            'thr_proportion_months' => $entitlement->months_worked,
            'thr_amount' => $entitlement->amount,
            'thr_calculation_breakdown' => $entitlement->calculation_breakdown,
        ]);

        $entitlement->update([
            'status' => 'GENERATED',
            'paid_payroll_id' => $payroll->id,
        ]);

        return $payroll;
    }
}
