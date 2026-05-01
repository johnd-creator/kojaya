<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartStock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'spare_part_id',
        'warehouse_id',
        'bin_location_id',
        'quantity',
        'reserved_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
        ];
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) ($this->quantity - $this->reserved_quantity);
    }

    public function reserveStock(float $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;

        return $this->save();
    }

    public function releaseReservedStock(float $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->reserved_quantity -= $quantity;

        return $this->save();
    }

    public function deductStock(float $quantity): bool
    {
        if ($this->quantity < $quantity) {
            return false;
        }

        $this->quantity -= $quantity;
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);

        return $this->save();
    }
}
