<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativePayment extends Model
{
    protected $fillable = [
        'cooperative_member_id',
        'cooperative_dues_invoice_id',
        'user_id',
        'amount',
        'payment_method',
        'paid_at',
        'status',
        'proof_path',
        'reference_no',
        'notes',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CooperativeDuesInvoice::class, 'cooperative_dues_invoice_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CooperativeLedgerEntry::class);
    }
}
