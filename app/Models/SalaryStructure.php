<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'employee_type',
        'job_grade_id',
        'organization_id',
        'min_tenure_months',
        'max_tenure_months',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalaryStructureItem::class);
    }

    /**
     * Lookup the most appropriate salary structure for an employee at a given period.
     * Preference order: org-specific > global (null org), then most recent effective_from.
     */
    public static function lookupFor(Employee $employee, Carbon $period): ?self
    {
        $tenureMonths = $employee->hire_date
            ? Carbon::parse($employee->hire_date)->diffInMonths($period)
            : 0;

        return self::query()
            ->where('employee_type', $employee->employee_type)
            ->where('job_grade_id', $employee->job_grade_id)
            ->where(function ($q) use ($employee) {
                $q->where('organization_id', $employee->organization_id)
                    ->orWhereNull('organization_id');
            })
            ->where('min_tenure_months', '<=', $tenureMonths)
            ->where(function ($q) use ($tenureMonths) {
                $q->whereNull('max_tenure_months')
                    ->orWhere('max_tenure_months', '>=', $tenureMonths);
            })
            ->where('effective_from', '<=', $period)
            ->where(function ($q) use ($period) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $period);
            })
            ->orderByRaw('CASE WHEN organization_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByDesc('effective_from')
            ->with('items.componentType')
            ->first();
    }

    /**
     * Sum all taxable components.
     */
    public function totalTaxable(): float
    {
        return $this->items
            ->filter(fn ($item) => $item->componentType?->is_taxable)
            ->sum('amount');
    }

    /**
     * Sum all components (gross salary).
     */
    public function totalGross(): float
    {
        return $this->items->sum('amount');
    }
}
