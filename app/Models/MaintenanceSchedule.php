<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id',
        'type',
        'frequency',
        'interval_value',
        'maintenance_checklist_id',
        'next_due_date',
        'last_meter_reading',
        'target_meter_reading',
        'priority',
        'assigned_to',
        'instructions',
        'is_active',
        'last_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'next_due_date' => 'date',
            'last_meter_reading' => 'decimal:2',
            'target_meter_reading' => 'decimal:2',
            'is_active' => 'boolean',
            'last_completed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(MaintenanceChecklist::class, 'maintenance_checklist_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->type === 'TIME_BASED' && $this->next_due_date) {
            return $this->next_due_date->isPast();
        }

        if ($this->type === 'METER_BASED' && $this->target_meter_reading) {
            return $this->last_meter_reading >= $this->target_meter_reading;
        }

        return false;
    }

    public function scheduleNextDueDate(): void
    {
        if ($this->type !== 'TIME_BASED') {
            return;
        }

        $lastDate = $this->last_completed_at ? $this->last_completed_at : now();
        $interval = $this->interval_value;

        $this->next_due_date = match ($this->frequency) {
            'DAILY' => $lastDate->addDays($interval),
            'WEEKLY' => $lastDate->addWeeks($interval),
            'MONTHLY' => $lastDate->addMonths($interval),
            'QUARTERLY' => $lastDate->addQuarters($interval),
            'YEARLY' => $lastDate->addYears($interval),
            default => $lastDate->addDays($interval),
        };

        $this->save();
    }
}
