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
        'status',
        'notes',
        'work_shift_id',
        'scheduled_end_time',
        'is_overtime',
        'overtime_hours',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
