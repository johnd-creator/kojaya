<?php

namespace App\Services;

class BpjsCalculationService
{
    private const KESEHATAN_MAX_SALARY = 12_000_000;

    private const KESEHATAN_EMPLOYEE_RATE = 0.01;

    private const KESEHATAN_EMPLOYER_RATE = 0.04;

    private const KETENAGAKERJAAN_MAX_SALARY_JHT_JP = 12_000_000;

    private const KETENAGAKERJAAN_MAX_SALARY_JKK_JKM = 9_000_000;

    private const JHT_EMPLOYEE_RATE = 0.02;

    private const JHT_EMPLOYER_RATE = 0.037;

    private const JP_EMPLOYEE_RATE = 0.01;

    private const JP_EMPLOYER_RATE = 0.01;

    private const JKK_RATE = 0.0089;

    private const JKM_RATE = 0.003;

    public function calculate(float $monthlyBasicSalary): array
    {
        $kesehatan = $this->calculateBpjsKesehatan($monthlyBasicSalary);
        $jht = $this->calculateJHT($monthlyBasicSalary);
        $jp = $this->calculateJP($monthlyBasicSalary);
        $jkk = $this->calculateJKK($monthlyBasicSalary);
        $jkm = $this->calculateJKM($monthlyBasicSalary);

        $totalEmployeeDeduction = $kesehatan['employee'] + $jht['employee'] + $jp['employee'];
        $totalEmployerContribution = $kesehatan['employer'] + $jht['employer'] + $jp['employer'] + $jkk['amount'] + $jkm['amount'];

        return [
            'bpjs_kesehatan' => $kesehatan,
            'bpjs_jht' => $jht,
            'bpjs_jp' => $jp,
            'bpjs_jkk' => $jkk,
            'bpjs_jkm' => $jkm,
            'total_employee_deduction' => $totalEmployeeDeduction,
            'total_employer_contribution' => $totalEmployerContribution,
            'total_bpjs' => $totalEmployeeDeduction + $totalEmployerContribution,
            'breakdown' => [
                'kesehatan_base_salary' => $kesehatan['base_salary'],
                'ketenagakerjaan_base_salary' => $jht['base_salary'],
            ],
        ];
    }

    private function calculateBpjsKesehatan(float $salary): array
    {
        $baseSalary = min($salary, self::KESEHATAN_MAX_SALARY);
        $employeeDeduction = $baseSalary * self::KESEHATAN_EMPLOYEE_RATE;
        $employerContribution = $baseSalary * self::KESEHATAN_EMPLOYER_RATE;

        return [
            'base_salary' => $baseSalary,
            'employee' => round($employeeDeduction, 2),
            'employer' => round($employerContribution, 2),
            'rate_employee' => self::KESEHATAN_EMPLOYEE_RATE * 100,
            'rate_employer' => self::KESEHATAN_EMPLOYER_RATE * 100,
        ];
    }

    private function calculateJHT(float $salary): array
    {
        $baseSalary = min($salary, self::KETENAGAKERJAAN_MAX_SALARY_JHT_JP);
        $employeeDeduction = $baseSalary * self::JHT_EMPLOYEE_RATE;
        $employerContribution = $baseSalary * self::JHT_EMPLOYER_RATE;

        return [
            'base_salary' => $baseSalary,
            'employee' => round($employeeDeduction, 2),
            'employer' => round($employerContribution, 2),
            'rate_employee' => self::JHT_EMPLOYEE_RATE * 100,
            'rate_employer' => self::JHT_EMPLOYER_RATE * 100,
        ];
    }

    private function calculateJP(float $salary): array
    {
        $baseSalary = min($salary, self::KETENAGAKERJAAN_MAX_SALARY_JHT_JP);
        $employeeDeduction = $baseSalary * self::JP_EMPLOYEE_RATE;
        $employerContribution = $baseSalary * self::JP_EMPLOYER_RATE;

        return [
            'base_salary' => $baseSalary,
            'employee' => round($employeeDeduction, 2),
            'employer' => round($employerContribution, 2),
            'rate_employee' => self::JP_EMPLOYEE_RATE * 100,
            'rate_employer' => self::JP_EMPLOYER_RATE * 100,
        ];
    }

    private function calculateJKK(float $salary): array
    {
        $baseSalary = min($salary, self::KETENAGAKERJAAN_MAX_SALARY_JKK_JKM);
        $amount = $baseSalary * self::JKK_RATE;

        return [
            'base_salary' => $baseSalary,
            'amount' => round($amount, 2),
            'rate' => self::JKK_RATE * 100,
        ];
    }

    private function calculateJKM(float $salary): array
    {
        $baseSalary = min($salary, self::KETENAGAKERJAAN_MAX_SALARY_JKK_JKM);
        $amount = $baseSalary * self::JKM_RATE;

        return [
            'base_salary' => $baseSalary,
            'amount' => round($amount, 2),
            'rate' => self::JKM_RATE * 100,
        ];
    }
}
