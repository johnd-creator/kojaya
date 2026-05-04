<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use HasFactory, HasUuids;

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

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress_percentage' => 'integer',
            'estimated_hours' => 'integer',
            'actual_hours' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_dependencies', 'task_id', 'predecessor_id')
            ->withPivot('id', 'type', 'lag_days')
            ->withTimestamps();
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTask::class, 'project_task_dependencies', 'predecessor_id', 'task_id')
            ->withPivot('id', 'type', 'lag_days')
            ->withTimestamps();
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeForProject(Builder $query, string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }
}
