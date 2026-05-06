<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy',
        'clock_in_device_id',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy',
        'clock_out_device_id',
        'status',
        'notes',
        'mobile_audit',
        'work_shift_id',
        'scheduled_end_time',
        'is_overtime',
        'overtime_hours',
    ];

    protected function casts(): array
    {
        return [
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_in_accuracy' => 'decimal:2',
            'clock_out_latitude' => 'decimal:7',
            'clock_out_longitude' => 'decimal:7',
            'clock_out_accuracy' => 'decimal:2',
            'mobile_audit' => 'array',
            'is_overtime' => 'boolean',
            'overtime_hours' => 'decimal:2',
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
}
