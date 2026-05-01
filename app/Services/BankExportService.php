<?php

namespace App\Services;

use App\Models\Payroll;
use Illuminate\Support\Collection;

class BankExportService
{
    public function exportPayrollToBank(string $payrollBatchId, string $bankCode)
    {
        $payrolls = Payroll::where('payroll_batch_id', $payrollBatchId)
            ->with('employee')
            ->get();

        return match ($bankCode) {
            'bni' => $this->exportToBNI($payrolls),
            'mandiri' => $this->exportToMandiri($payrolls),
            default => throw new \InvalidArgumentException("Unsupported bank code: {$bankCode}"),
        };
    }

    private function exportToBNI(Collection $payrolls): string
    {
        $lines = [];

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;

            if (! $employee->bank_account_number || $employee->bank_name !== 'BNI') {
                continue;
            }

            $lines[] = implode(',', [
                $employee->bank_account_number,
                $employee->bank_account_holder ?? $employee->first_name.' '.$employee->last_name,
                number_format($payroll->net_salary, 2, '.', ''),
                now()->format('Ymd'),
                'PAYROLL-'.$payroll->payroll_batch_id,
            ]);
        }

        return implode("\n", $lines);
    }

    private function exportToMandiri(Collection $payrolls): string
    {
        $lines = [];

        $lines[] = 'HDR'.str_pad(count($payrolls), 6, '0', STR_PAD_LEFT).now()->format('Ymd');

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;

            if (! $employee->bank_account_number || $employee->bank_name !== 'MANDIRI') {
                continue;
            }

            $lines[] = implode('', [
                'DTL',
                str_pad($employee->bank_account_number, 20, ' '),
                str_pad(($employee->bank_account_holder ?? $employee->first_name.' '.$employee->last_name), 50, ' '),
                str_pad(number_format($payroll->net_salary, 0, '', ''), 15, '0', STR_PAD_LEFT),
                'IDR',
            ]);
        }

        return implode("\n", $lines);
    }

    public function downloadFile(string $content, string $filename)
    {
        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
