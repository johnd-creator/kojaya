<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'pos_product_id',
        'quantity',
        'unit_price',
        'cost_price',
        'unit_profit',
        'line_total',
        'line_profit',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'unit_profit' => 'decimal:2',
            'line_total' => 'decimal:2',
            'line_profit' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
