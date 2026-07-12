<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliationIncident extends Model
{
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_RESOLVED = 'RESOLVED';

    public const TYPE_PAID_AFTER_EXPIRY = 'paid_after_expiry';

    public const TYPE_PAID_AFTER_RELEASE = 'paid_after_release';

    public const TYPE_AMOUNT_MISMATCH = 'amount_mismatch';

    public const TYPE_PAID_WITHOUT_RESERVATION = 'paid_without_reservation';

    protected $fillable = [
        'member_payment_intent_id',
        'gateway_reference',
        'deduplication_key',
        'incident_type',
        'status',
        'provider_status',
        'provider_reference',
        'expected_amount_minor',
        'actual_amount_minor',
        'webhook_payload',
        'resolution',
        'incident_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'webhook_payload' => 'array',
            'resolution' => 'array',
            'incident_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(MemberPaymentIntent::class, 'member_payment_intent_id');
    }
}
