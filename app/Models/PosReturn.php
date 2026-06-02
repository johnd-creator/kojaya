<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_transaction_id',
        'cooperative_member_id',
        'cashier_id',
        'return_no',
        'status',
        'total_amount',
        'points_reversed',
        'reason',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'returned_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosReturnItem::class);
    }
}
