<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'is_working_here' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function relatedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'related_employee_id');
    }
}
