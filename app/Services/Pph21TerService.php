<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TaxRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Pph21TerService
{
    public function calculate(Employee $employee, float $monthlyBasicSalary, float $monthlyAllowance = 0, ?string $period = null): array
    {
        $rule = $this->resolveRule($period);
        $monthlyGross = $monthlyBasicSalary + $monthlyAllowance;
        $annualGross = $monthlyGross * 12;

        $biayaJabatan = $this->calculateBiayaJabatan($annualGross, $rule);
        $netto = $annualGross - $biayaJabatan;

        $ptkp = $this->getPtkpAmount($employee->phtkp_status ?? 'TK/0', $rule);
        $pkp = max(0, $netto - $ptkp);

        $annualTax = $this->calculateProgressiveTax($pkp, $rule);
        $monthlyTax = $annualTax / 12;
        $hasNpwp = $employee->is_npwp_available || ! empty($employee->npwp_number);

        if (! $hasNpwp) {
            $monthlyTax *= (1 + (float) $rule['no_npwp_surcharge_rate']);
        }

        return [
            'tax_rule_code' => $rule['code'],
            'tax_rule_reference' => $rule['regulation_reference'],
            'monthly_gross' => $monthlyGross,
            'annual_gross' => $annualGross,
            'biaya_jabatan' => $biayaJabatan,
            'netto' => $netto,
            'ptkp_status' => $employee->phtkp_status ?? 'TK/0',
            'ptkp_amount' => $ptkp,
            'pkp' => $pkp,
            'annual_tax' => $annualTax,
            'monthly_tax' => round($monthlyTax, 2),
            'has_npwp' => $hasNpwp,
            'no_npwp_surcharge' => $hasNpwp ? 0 : (float) $rule['no_npwp_surcharge_rate'] * 100,
            'breakdown' => $this->getTaxBreakdown($pkp, $rule),
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     regulation_reference: string|null,
     *     ptkp_amounts: array<string, int|float>,
     *     progressive_layers: list<array{name: string, max: int|float|null, rate: float}>,
     *     biaya_jabatan_rate: float,
     *     biaya_jabatan_max: int|float,
     *     no_npwp_surcharge_rate: float
     * }
     */
    private function resolveRule(?string $period): array
    {
        $default = TaxRule::defaultPph21Ter2024();
        $effectiveDate = $this->effectiveDate($period);

        try {
            if (! Schema::hasTable('tax_rules')) {
                return $default;
            }

            $rule = TaxRule::query()
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $effectiveDate)
                ->where(function ($query) use ($effectiveDate): void {
                    $query
                        ->whereNull('effective_until')
                        ->orWhereDate('effective_until', '>=', $effectiveDate);
                })
                ->orderByDesc('effective_from')
                ->first();

            if ($rule instanceof TaxRule) {
                return [
                    ...$default,
                    ...$rule->toArray(),
                ];
            }
        } catch (Throwable) {
            return $default;
        }

        return $default;
    }

    private function effectiveDate(?string $period): string
    {
        if ($period === null || $period === '') {
            return now()->toDateString();
        }

        if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', $period.'-01')->toDateString();
        }

        return CarbonImmutable::parse($period)->toDateString();
    }

    /**
     * @param  array{biaya_jabatan_rate: float, biaya_jabatan_max: int|float}  $rule
     */
    private function calculateBiayaJabatan(float $annualGross, array $rule): float
    {
        $biayaJabatan = $annualGross * (float) $rule['biaya_jabatan_rate'];

        return min($biayaJabatan, (float) $rule['biaya_jabatan_max']);
    }

    /**
     * @param  array{ptkp_amounts: array<string, int|float>}  $rule
     */
    private function getPtkpAmount(string $status, array $rule): float
    {
        return (float) ($rule['ptkp_amounts'][$status] ?? $rule['ptkp_amounts']['TK/0']);
    }

    /**
     * @param  array{progressive_layers: list<array{name: string, max: int|float|null, rate: float}>}  $rule
     */
    private function calculateProgressiveTax(float $pkp, array $rule): float
    {
        if ($pkp <= 0) {
            return 0;
        }

        $tax = 0.0;
        $remainingPkp = $pkp;

        $previousMax = 0;

        foreach ($rule['progressive_layers'] as $layer) {
            if ($remainingPkp <= 0) {
                break;
            }

            $layerMax = $layer['max'] ?? PHP_INT_MAX;
            $taxableInLayer = min($remainingPkp, (float) $layerMax - $previousMax);
            $tax += $taxableInLayer * (float) $layer['rate'];
            $remainingPkp -= $taxableInLayer;
            $previousMax = (float) $layerMax;
        }

        return $tax;
    }

    /**
     * @param  array{progressive_layers: list<array{name: string, max: int|float|null, rate: float}>}  $rule
     */
    private function getTaxBreakdown(float $pkp, array $rule): array
    {
        if ($pkp <= 0) {
            return [];
        }

        $breakdown = [];
        $remainingPkp = $pkp;

        $previousMax = 0;

        foreach ($rule['progressive_layers'] as $layer) {
            if ($remainingPkp <= 0) {
                break;
            }

            $layerMax = $layer['max'] ?? PHP_INT_MAX;
            $taxableInLayer = min($remainingPkp, (float) $layerMax - $previousMax);
            if ($taxableInLayer > 0) {
                $taxInLayer = $taxableInLayer * (float) $layer['rate'];
                $breakdown[] = [
                    'layer' => $layer['name'],
                    'taxable_amount' => $taxableInLayer,
                    'rate' => (float) $layer['rate'] * 100,
                    'tax_amount' => $taxInLayer,
                ];
            }
            $remainingPkp -= $taxableInLayer;
            $previousMax = (float) $layerMax;
        }

        return $breakdown;
    }
}
