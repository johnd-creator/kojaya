<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'parent_task_id',
        'start_date',
        'end_date',
        'assigned_to',
        'status',
        'progress_percentage',
        'estimated_hours',
        'actual_hours',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'integer',
        'estimated_hours' => 'integer',
        'actual_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function predecessors()
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_dependencies', 'task_id', 'predecessor_id')
            ->withPivot('id', 'type', 'lag_days')
            ->withTimestamps();
    }

    public function successors()
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_dependencies', 'predecessor_id', 'task_id')
            ->withPivot('id', 'type', 'lag_days')
            ->withTimestamps();
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
