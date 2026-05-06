<?php

namespace App\Models;

use App\Models\Traits\HasApprovalLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativePayment extends Model
{
    use HasApprovalLog;

    protected $fillable = [
        'cooperative_member_id',
        'cooperative_dues_invoice_id',
        'user_id',
        'amount',
        'payment_method',
        'gateway_provider',
        'gateway_reference',
        'gateway_status',
        'gateway_payload',
        'paid_at',
        'status',
        'proof_path',
        'reference_no',
        'receipt_no',
        'receipt_issued_at',
        'notes',
        'approved_at',
        'approved_by',
        'reconciled_at',
        'reconciled_by',
        'reconciliation_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'approved_at' => 'datetime',
            'receipt_issued_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'gateway_payload' => 'array',
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
