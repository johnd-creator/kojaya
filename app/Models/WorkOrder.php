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
        'assigned_to',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
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
