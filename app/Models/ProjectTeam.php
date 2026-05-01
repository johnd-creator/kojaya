<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProjectTeam extends Model
{
    use HasUuids;

    protected $table = 'project_team';

    protected $fillable = [
        'project_id',
        'employee_id',
        'role',
        'start_date',
        'end_date',
        'daily_rate_cost',
        'notes',
        'status',
        'has_ppe',
        'has_uniform',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'daily_rate_cost' => 'decimal:2',
        'has_ppe' => 'boolean',
        'has_uniform' => 'boolean',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', now());
        });
    }
}
