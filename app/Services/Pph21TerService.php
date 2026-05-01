<?php

namespace App\Services;

use App\Models\Employee;

class Pph21TerService
{
    private const PTKP_TK0 = 54_000_000;

    private const PTKP_TK1 = 58_500_000;

    private const PTKP_TK2 = 63_000_000;

    private const PTKP_TK3 = 67_500_000;

    private const PTKP_K0 = 58_500_000;

    private const PTKP_K1 = 63_000_000;

    private const PTKP_K2 = 67_500_000;

    private const PTKP_K3 = 72_000_000;

    private const BIAYA_JABATAN_MAX = 6_000_000;

    private const BIAYA_JABATAN_RATE = 0.05;

    private const TARIF_LAYER_1 = ['max' => 60_000_000, 'rate' => 0.05];

    private const TARIF_LAYER_2 = ['max' => 250_000_000, 'rate' => 0.15];

    private const TARIF_LAYER_3 = ['max' => 500_000_000, 'rate' => 0.25];

    private const TARIF_LAYER_4 = ['max' => 5_000_000_000, 'rate' => 0.30];

    private const TARIF_LAYER_5 = ['max' => PHP_INT_MAX, 'rate' => 0.35];

    public function calculate(Employee $employee, float $monthlyBasicSalary, float $monthlyAllowance = 0): array
    {
        $monthlyGross = $monthlyBasicSalary + $monthlyAllowance;
        $annualGross = $monthlyGross * 12;

        $biayaJabatan = $this->calculateBiayaJabatan($annualGross);
        $netto = $annualGross - $biayaJabatan;

        $ptkp = $this->getPtkpAmount($employee->phtkp_status ?? 'TK/0');
        $pkp = max(0, $netto - $ptkp);

        $annualTax = $this->calculateProgressiveTax($pkp);
        $monthlyTax = $annualTax / 12;

        if (! $employee->is_npwp_available && ! $employee->npwp_number) {
            $monthlyTax *= 1.20;
        }

        return [
            'monthly_gross' => $monthlyGross,
            'annual_gross' => $annualGross,
            'biaya_jabatan' => $biayaJabatan,
            'netto' => $netto,
            'ptkp_status' => $employee->phtkp_status ?? 'TK/0',
            'ptkp_amount' => $ptkp,
            'pkp' => $pkp,
            'annual_tax' => $annualTax,
            'monthly_tax' => round($monthlyTax, 2),
            'has_npwp' => $employee->is_npwp_available || ! empty($employee->npwp_number),
            'no_npwp_surcharge' => (! $employee->is_npwp_available && ! $employee->npwp_number) ? 20 : 0,
            'breakdown' => $this->getTaxBreakdown($pkp),
        ];
    }

    private function calculateBiayaJabatan(float $annualGross): float
    {
        $biayaJabatan = $annualGross * self::BIAYA_JABATAN_RATE;

        return min($biayaJabatan, self::BIAYA_JABATAN_MAX);
    }

    private function getPtkpAmount(string $status): int
    {
        return match ($status) {
            'TK/0' => self::PTKP_TK0,
            'TK/1' => self::PTKP_TK1,
            'TK/2' => self::PTKP_TK2,
            'TK/3' => self::PTKP_TK3,
            'K/0' => self::PTKP_K0,
            'K/1' => self::PTKP_K1,
            'K/2' => self::PTKP_K2,
            'K/3' => self::PTKP_K3,
            default => self::PTKP_TK0,
        };
    }

    private function calculateProgressiveTax(float $pkp): float
    {
        if ($pkp <= 0) {
            return 0;
        }

        $tax = 0.0;
        $remainingPkp = $pkp;

        $layers = [self::TARIF_LAYER_1, self::TARIF_LAYER_2, self::TARIF_LAYER_3, self::TARIF_LAYER_4, self::TARIF_LAYER_5];
        $previousMax = 0;

        foreach ($layers as $layer) {
            if ($remainingPkp <= 0) {
                break;
            }

            $taxableInLayer = min($remainingPkp, $layer['max'] - $previousMax);
            $tax += $taxableInLayer * $layer['rate'];
            $remainingPkp -= $taxableInLayer;
            $previousMax = $layer['max'];
        }

        return $tax;
    }

    private function getTaxBreakdown(float $pkp): array
    {
        if ($pkp <= 0) {
            return [];
        }

        $breakdown = [];
        $remainingPkp = $pkp;

        $layers = [
            ['name' => 'Layer 1 (0-60jt)', 'max' => 60_000_000, 'rate' => 0.05],
            ['name' => 'Layer 2 (60-250jt)', 'max' => 250_000_000, 'rate' => 0.15],
            ['name' => 'Layer 3 (250-500jt)', 'max' => 500_000_000, 'rate' => 0.25],
            ['name' => 'Layer 4 (500jt-5M)', 'max' => 5_000_000_000, 'rate' => 0.30],
            ['name' => 'Layer 5 (>5M)', 'max' => PHP_INT_MAX, 'rate' => 0.35],
        ];

        $previousMax = 0;

        foreach ($layers as $layer) {
            if ($remainingPkp <= 0) {
                break;
            }

            $taxableInLayer = min($remainingPkp, $layer['max'] - $previousMax);
            if ($taxableInLayer > 0) {
                $taxInLayer = $taxableInLayer * $layer['rate'];
                $breakdown[] = [
                    'layer' => $layer['name'],
                    'taxable_amount' => $taxableInLayer,
                    'rate' => $layer['rate'] * 100,
                    'tax_amount' => $taxInLayer,
                ];
            }
            $remainingPkp -= $taxableInLayer;
            $previousMax = $layer['max'];
        }

        return $breakdown;
    }
}
