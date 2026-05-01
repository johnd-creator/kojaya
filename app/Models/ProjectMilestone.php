<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'status',
        'progress_percentage',
    ];

    protected $casts = [
        'due_date' => 'date',
        'progress_percentage' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['COMPLETED']);
    }
}
