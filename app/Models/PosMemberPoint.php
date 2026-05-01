<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosMemberPoint extends Model
{
    protected $fillable = [
        'cooperative_member_id',
        'pos_transaction_id',
        'year',
        'profit_amount',
        'points',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'profit_amount' => 'decimal:2',
            'posted_at' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
