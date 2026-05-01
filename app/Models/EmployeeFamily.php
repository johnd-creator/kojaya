<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeFamily extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'birth_date',
        'gender',
        'nik_ktp',
        'is_working_here',
        'related_employee_id',
    ];

    protected $casts = [
        'is_working_here' => 'boolean',
        'birth_date' => 'date',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function relatedEmployee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'related_employee_id');
    }
}
