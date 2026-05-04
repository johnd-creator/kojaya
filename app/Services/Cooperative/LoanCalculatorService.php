<?php

namespace App\Services\Cooperative;

use App\Models\LoanType;
use Carbon\CarbonImmutable;

class LoanCalculatorService
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(
        LoanType $loanType,
        float $principalAmount,
        int $termMonths,
        string $firstDueDate,
    ): array {
        $monthlyInterestAmount = round($principalAmount * ((float) $loanType->interest_rate / 100), 2);
        $basePrincipalAmount = round($principalAmount / $termMonths, 2);
        $remainingPrincipal = round($principalAmount, 2);
        $totalInterestAmount = round($monthlyInterestAmount * $termMonths, 2);
        $totalAmount = round($principalAmount + $totalInterestAmount + (float) $loanType->admin_fee, 2);
        $installmentAmount = round($basePrincipalAmount + $monthlyInterestAmount, 2);
        $schedule = [];

        $dueDate = CarbonImmutable::parse($firstDueDate);

        for ($installmentNo = 1; $installmentNo <= $termMonths; $installmentNo++) {
            $principalPortion = $installmentNo === $termMonths
                ? $remainingPrincipal
                : min($remainingPrincipal, $basePrincipalAmount);
            $feeAmount = $installmentNo === 1 ? (float) $loanType->admin_fee : 0;
            $amountDue = round($principalPortion + $monthlyInterestAmount + $feeAmount, 2);

            $schedule[] = [
                'installment_no' => $installmentNo,
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => round($principalPortion, 2),
                'interest_amount' => $monthlyInterestAmount,
                'fee_amount' => $feeAmount,
                'penalty_amount' => 0,
                'amount_due' => $amountDue,
            ];

            $remainingPrincipal = round($remainingPrincipal - $principalPortion, 2);
            $dueDate = $dueDate->addMonth();
        }

        return [
            'principal_amount' => round($principalAmount, 2),
            'interest_rate' => (float) $loanType->interest_rate,
            'admin_fee' => round((float) $loanType->admin_fee, 2),
            'late_fee_per_day' => round((float) $loanType->late_fee_per_day, 2),
            'term_months' => $termMonths,
            'installment_amount' => $installmentAmount,
            'total_interest_amount' => $totalInterestAmount,
            'total_amount' => $totalAmount,
            'first_due_date' => $firstDueDate,
            'schedule' => $schedule,
        ];
    }
}
