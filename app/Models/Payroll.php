<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'period',
        'basic_salary',
        'total_allowance',
        'total_deduction',
        'tax_amount',
        'bpjs_amount',
        'net_salary',
        'status',
        'is_thr',
        'thr_proportion_months',
        'thr_amount',
        'thr_calculation_breakdown',
        'pph21_calculation_breakdown',
        'bpjs_kesehatan_amount',
        'bpjs_jht_amount',
        'bpjs_jp_amount',
        'bpjs_jkk_amount',
        'bpjs_jkm_amount',
        'bpjs_calculation_breakdown',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function components(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollComponent::class);
    }

    protected function casts(): array
    {
        return [
            'pph21_calculation_breakdown' => 'array',
            'bpjs_calculation_breakdown' => 'array',
            'thr_calculation_breakdown' => 'array',
        ];
    }
}
