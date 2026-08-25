<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosProduct extends Model
{
    use HasFactory;

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'organization_id',
        'pos_category_id',
        'sku',
        'barcode',
        'name',
        'image_path',
        'brand',
        'variant',
        'unit',
        'rack_location',
        'cost_price',
        'sale_price',
        'stock',
        'minimum_stock',
        'is_active',
        'is_discontinued',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_discontinued' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return asset('storage/'.$this->image_path);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'pos_category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(PosStockMovement::class);
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_discontinued', false);
    }

    public function isSellable(): bool
    {
        return $this->is_active && ! $this->is_discontinued;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->minimum_stock > 0 && $this->stock <= $this->minimum_stock;
    }
}
