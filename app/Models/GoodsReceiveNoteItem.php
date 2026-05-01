<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiveNoteItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'goods_receive_note_id',
        'purchase_order_item_id',
        'received_qty',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'received_qty' => 'decimal:2',
        ];
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveNote::class, 'goods_receive_note_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }
}
