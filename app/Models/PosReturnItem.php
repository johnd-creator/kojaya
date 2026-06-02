<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturnItem extends Model
{
    protected $fillable = [
        'pos_return_id',
        'pos_transaction_item_id',
        'pos_product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'pos_return_id');
    }
}
