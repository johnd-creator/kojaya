<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'user_id',
        'organization_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'gender',
        'birth_date',
        'hire_date',
        'basic_salary',
        'status',
        'employee_type',
        'department_id',
        'position_id',
        'job_grade_id',
        'work_shift_id',
        'shift_group',
        'phtkp_status',
        'npwp_number',
        'is_npwp_available',
        'number_of_dependents',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function jobGrade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function workShift(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function families(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function thrEntitlements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ThrEntitlement::class);
    }

    public function attendanceCorrections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function leaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function overtimeRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Tenure in months from hire_date.
     */
    public function tenureMonths(): int
    {
        return $this->hire_date
            ? \Carbon\Carbon::parse($this->hire_date)->diffInMonths(now())
            : 0;
    }

    /**
     * Get today's shift roster entry for this employee based on their shift group.
     */
    public function todayRoster(): ?ShiftRoster
    {
        if (! $this->shift_group) {
            return null;
        }

        return ShiftRoster::todayFor($this->shift_group);
    }

    public function transfers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeTransfer::class);
    }

    public function pendingTransfers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->transfers()->where('status', 'PENDING');
    }

    public function certificates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function medicalCheckups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MedicalCheckup::class);
    }
}
