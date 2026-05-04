<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_category_id',
        'sku',
        'barcode',
        'name',
        'cost_price',
        'sale_price',
        'stock',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'pos_category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(PosStockMovement::class);
    }
}
