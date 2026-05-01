<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OvertimeRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'overtime_rule_id',
        'date',
        'start_time',
        'end_time',
        'total_hours',
        'reason',
        'evidence_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'total_hours' => 'decimal:2',
            'approved_at' => 'datetime',
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

    public function overtimeRule(): BelongsTo
    {
        return $this->belongsTo(OvertimeRule::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function overtimePayment(): HasOne
    {
        return $this->hasOne(OvertimePayment::class, 'overtime_request_id');
    }
}
