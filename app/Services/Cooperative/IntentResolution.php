<?php

namespace App\Services\Cooperative;

/**
 * Outcome of {@see MemberOrderIntentService::resolveOrCreate()}.
 */
final class IntentResolution
{
    public function __construct(
        public readonly \App\Models\MemberPaymentIntent $intent,
        public readonly bool $created,
    ) {}

    public function wasCreated(): bool
    {
        return $this->created;
    }

    public function wasReused(): bool
    {
        return ! $this->created;
    }
}
