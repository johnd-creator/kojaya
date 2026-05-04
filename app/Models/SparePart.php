<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SparePart extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'specification',
        'unit',
        'min_stock',
        'max_stock',
        'reorder_level',
        'category',
        'organization_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'decimal:2',
            'max_stock' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(SparePartStock::class);
    }

    public function getTotalStockAttribute(): float
    {
        return (float) $this->stocks()->sum('quantity');
    }

    public function getAvailableStockAttribute(): float
    {
        return (float) $this->stocks()->sum(DB::raw('quantity - reserved_quantity'));
    }

    public function isBelowMinStock(): bool
    {
        return $this->total_stock < $this->min_stock;
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->total_stock <= $this->reorder_level;
    }
}
