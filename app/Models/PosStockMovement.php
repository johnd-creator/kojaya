<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PosStockMovement extends Model
{
    protected $fillable = [
        'pos_product_id',
        'source_type',
        'source_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
