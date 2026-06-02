<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThrEntitlement extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'year',
        'months_worked',
        'base_salary',
        'amount',
        'status',
        'calculated_at',
        'paid_payroll_id',
        'calculation_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'amount' => 'decimal:2',
            'calculated_at' => 'datetime',
            'calculation_breakdown' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function paidPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'paid_payroll_id');
    }
}
