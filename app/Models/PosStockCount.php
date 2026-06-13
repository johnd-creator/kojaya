<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosStockCount extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_REVIEW = 'REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'count_no',
        'pos_inventory_location_id',
        'requested_by',
        'approved_by',
        'counted_at',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'date',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'pos_inventory_location_id');
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
        return $this->hasMany(PosStockCountItem::class);
    }
}
