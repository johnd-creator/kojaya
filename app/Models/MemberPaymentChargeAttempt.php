<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPaymentChargeAttempt extends Model
{
    public const STATE_PREPARING = 'PREPARING';

    public const STATE_SENT = 'SENT';

    public const STATE_CONFIRMED = 'CONFIRMED';

    public const STATE_FAILED = 'FAILED';

    public const STATE_UNKNOWN = 'UNKNOWN';

    public const STATE_ORPHANED = 'ORPHANED';

    protected $fillable = [
        'member_payment_intent_id',
        'attempt',
        'idempotency_key',
        'provider_order_id',
        'state',
        'provider_reference',
        'request_payload',
        'response_payload',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(MemberPaymentIntent::class, 'member_payment_intent_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->state, [self::STATE_CONFIRMED, self::STATE_FAILED], true);
    }

    public function isResolvable(): bool
    {
        return in_array($this->state, [self::STATE_UNKNOWN, self::STATE_ORPHANED], true);
    }
}
