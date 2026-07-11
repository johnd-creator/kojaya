<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPaymentIntent extends Model
{
    /** @use HasFactory<\Database\Factories\MemberPaymentIntentFactory> */
    use HasFactory;

    public const PAYABLE_DUES_INVOICE = 'dues_invoice';

    public const PAYABLE_LOAN_INSTALLMENT = 'loan_installment';

    public const PAYABLE_POS_CREDIT = 'pos_credit';

    public const PAYABLE_COFFEE_ORDER = 'coffee_order';

    public const PAYABLE_STORE_ORDER = 'store_order';

    protected $fillable = [
        'user_id',
        'cooperative_member_id',
        'payable_type',
        'payable_id',
        'client_reference',
        'amount',
        'channel',
        'gateway_provider',
        'gateway_reference',
        'gateway_status',
        'gateway_payload',
        'metadata',
        'expires_at',
        'settled_at',
        'settled_by_service',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }
}
