<?php

namespace App\Services\Integrations;

class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $gatewayReference,
        public readonly string $status,
        public readonly string $paymentMethod,
        public readonly string $channel,
        public readonly string $amount,
        public readonly ?int $amountMinor = null,
        public readonly ?string $reconciliationReference = null,
        public readonly ?string $fraudStatus = null,
        public readonly array $rawPayload = [],
    ) {}
}
