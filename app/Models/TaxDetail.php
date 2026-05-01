<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'period',
        'calculation_type',
        'gross_income',
        'biaya_jabatan',
        'netto',
        'ptkp_status',
        'ptkp_amount',
        'pkp',
        'pph21_annual',
        'pph21_monthly',
        'npwp_available',
        'no_npwp_surcharge_percent',
        'final_pph21_amount',
        'bpjs_kesehatan_amount',
        'bpjs_jht_amount',
        'bpjs_jp_amount',
        'bpjs_jkk_amount',
        'bpjs_jkm_amount',
        'total_bpjs',
        'calculation_breakdown',
        'external_service_ref',
        'calculation_source',
    ];

    protected function casts(): array
    {
        return [
            'gross_income' => 'float',
            'biaya_jabatan' => 'float',
            'netto' => 'float',
            'ptkp_amount' => 'float',
            'pkp' => 'float',
            'pph21_annual' => 'float',
            'pph21_monthly' => 'float',
            'final_pph21_amount' => 'float',
            'bpjs_kesehatan_amount' => 'float',
            'bpjs_jht_amount' => 'float',
            'bpjs_jp_amount' => 'float',
            'bpjs_jkk_amount' => 'float',
            'bpjs_jkm_amount' => 'float',
            'total_bpjs' => 'float',
            'calculation_breakdown' => 'array',
            'npwp_available' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeFromExternalService($query)
    {
        return $query->where('calculation_source', 'EXTERNAL');
    }

    public function scopeFromInternalCalculation($query)
    {
        return $query->where('calculation_source', 'INTERNAL');
    }

    public function getTotalDeductionAttribute(): float
    {
        return $this->final_pph21_amount + $this->total_bpjs;
    }

    public function markAsExternalCalculation(string $serviceRef): void
    {
        $this->update([
            'calculation_source' => 'EXTERNAL',
            'external_service_ref' => $serviceRef,
        ]);
    }

    public function markAsInternalCalculation(): void
    {
        $this->update([
            'calculation_source' => 'INTERNAL',
            'external_service_ref' => null,
        ]);
    }
}
