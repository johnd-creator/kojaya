<?php

namespace App\Models;

use App\Enums\AttendanceCorrectionStatus;
use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'attendance_id',
        'requested_by',
        'reviewed_by',
        'date',
        'corrected_clock_in',
        'corrected_clock_out',
        'reason',
        'evidence_path',
        'status',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reviewed_at' => 'datetime',
            'status' => AttendanceCorrectionStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
