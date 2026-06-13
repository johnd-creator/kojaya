<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosStockReceipt extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = [
        'receipt_no',
        'pos_supplier_id',
        'pos_inventory_location_id',
        'received_by',
        'reference_no',
        'received_at',
        'total_amount',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PosSupplier::class, 'pos_supplier_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'pos_inventory_location_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosStockReceiptItem::class);
    }
}
