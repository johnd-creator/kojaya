<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosStockTransfer extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'transfer_no',
        'from_location_id',
        'to_location_id',
        'requested_by',
        'approved_by',
        'transferred_at',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'date',
        ];
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'to_location_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosStockTransferItem::class);
    }
}
