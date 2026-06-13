<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosStockReceiptItem extends Model
{
    protected $fillable = [
        'pos_stock_receipt_id',
        'pos_product_id',
        'quantity',
        'unit_cost',
        'line_total',
        'batch_no',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'expired_at' => 'date',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PosStockReceipt::class, 'pos_stock_receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
