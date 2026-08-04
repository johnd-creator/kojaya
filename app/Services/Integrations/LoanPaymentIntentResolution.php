<?php

namespace App\Services\Integrations;

use App\Models\MemberPaymentIntent;

final class LoanPaymentIntentResolution
{
    public function __construct(
        public readonly MemberPaymentIntent $intent,
        public readonly bool $created,
        public readonly string $requestedChannel,
    ) {}
}
