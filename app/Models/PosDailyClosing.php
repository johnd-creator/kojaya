<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDailyClosing extends Model
{
    protected $fillable = [
        'closing_date',
        'closed_by',
        'closed_at',
        'transaction_count',
        'gross_sales',
        'total_discount',
        'total_void',
        'total_return',
        'net_sales',
        'member_credit_outstanding',
        'payment_summary',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'closed_at' => 'datetime',
            'transaction_count' => 'integer',
            'gross_sales' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'total_void' => 'decimal:2',
            'total_return' => 'decimal:2',
            'net_sales' => 'decimal:2',
            'member_credit_outstanding' => 'decimal:2',
            'payment_summary' => 'array',
            'is_locked' => 'boolean',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
