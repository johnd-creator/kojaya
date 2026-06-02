<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDependency extends Model
{
    use HasUuids;

    protected $fillable = [
        'predecessor_id',
        'task_id',
        'type',
        'lag_days',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'predecessor_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }
}
