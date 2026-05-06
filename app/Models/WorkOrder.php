<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id',
        'organization_id',
        'type',
        'priority',
        'status',
        'description',
        'scheduled_date',
        'assigned_to',
        'started_at',
        'start_latitude',
        'start_longitude',
        'start_accuracy',
        'completed_at',
        'completion_latitude',
        'completion_longitude',
        'completion_accuracy',
        'completion_notes',
        'reviewed_at',
        'reviewed_by',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'escalated_at',
        'escalated_by',
        'escalation_type',
        'escalation_reason',
        'reassignment_requested_to',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'start_latitude' => 'decimal:7',
            'start_longitude' => 'decimal:7',
            'start_accuracy' => 'decimal:2',
            'completed_at' => 'datetime',
            'completion_latitude' => 'decimal:7',
            'completion_longitude' => 'decimal:7',
            'completion_accuracy' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(WorkOrderChecklist::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(WorkOrderTimeline::class);
    }

    public function syncRequests(): HasMany
    {
        return $this->hasMany(WorkOrderSyncRequest::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($workOrder) {
            if ($workOrder->isDirty('status') && $workOrder->status === 'COMPLETED') {
                $workOrder->deductPartsStock();
            }
        });
    }

    public function deductPartsStock(): void
    {
        foreach ($this->parts as $part) {
            $stock = SparePartStock::where('spare_part_id', $part->spare_part_id)
                ->where('warehouse_id', $part->warehouse_id)
                ->first();

            if ($stock) {
                $stock->deductStock($part->quantity_used);
            }

            $part->used_at = now();
            $part->used_by = auth()->id();
            $part->save();
        }
    }

    public function canCompleteWithCurrentStock(): bool
    {
        foreach ($this->parts as $part) {
            $stock = SparePartStock::where('spare_part_id', $part->spare_part_id)
                ->where('warehouse_id', $part->warehouse_id)
                ->first();

            if (! $stock || $stock->available_quantity < $part->quantity_used) {
                return false;
            }
        }

        return true;
    }
}
