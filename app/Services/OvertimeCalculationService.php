<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeRequest;

class OvertimeCalculationService
{
    private const STANDARD_WORK_DAYS_PER_MONTH = 22;

    public function calculateHourlyRate(Employee $employee): float
    {
        $basicSalary = $employee->basic_salary ?? 0;

        return round($basicSalary / self::STANDARD_WORK_DAYS_PER_MONTH / 8, 2);
    }

    public function calculateOvertimePayment(OvertimeRequest $request, float $hourlyRate): array
    {
        $rule = $request->overtimeRule;
        $hours = $request->total_hours;
        $multiplier = $rule->multiplier;

        $overtimeRate = $hourlyRate * $multiplier;
        $amount = round($hours * $overtimeRate, 2);

        return [
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'multiplier' => $multiplier,
            'overtime_rate' => $overtimeRate,
            'amount' => $amount,
            'rule_name' => $rule->name,
        ];
    }

    public function getMonthlyOvertimeForEmployee(int $employeeId, string $period): array
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);

        return OvertimeRequest::where('employee_id', $employeeId)
            ->where('status', 'APPROVED')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('overtimeRule')
            ->get()
            ->toArray();
    }

    public function calculateTotalOvertimeForPeriod(int $employeeId, string $period, float $hourlyRate): array
    {
        $approvedRequests = $this->getMonthlyOvertimeForEmployee($employeeId, $period);

        $totalAmount = 0;
        $totalHours = 0;
        $breakdown = [];

        foreach ($approvedRequests as $request) {
            $calculation = $this->calculateOvertimePayment(
                OvertimeRequest::find($request['id']),
                $hourlyRate
            );

            $totalAmount += $calculation['amount'];
            $totalHours += $calculation['hours'];
            $breakdown[] = $calculation;
        }

        return [
            'total_hours' => round($totalHours, 2),
            'total_amount' => round($totalAmount, 2),
            'breakdown' => $breakdown,
            'request_count' => count($approvedRequests),
        ];
    }
}
