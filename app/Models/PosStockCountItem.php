<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosStockCountItem extends Model
{
    protected $fillable = [
        'pos_stock_count_id',
        'pos_product_id',
        'system_qty',
        'counted_qty',
        'difference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'counted_qty' => 'integer',
            'difference' => 'integer',
        ];
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(PosStockCount::class, 'pos_stock_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
