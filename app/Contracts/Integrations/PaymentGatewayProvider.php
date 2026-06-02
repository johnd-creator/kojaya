<?php

namespace App\Contracts\Integrations;

use App\Models\CooperativePayment;
use App\Services\Integrations\WebhookEvent;

interface PaymentGatewayProvider
{
    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null}
     */
    public function createCharge(CooperativePayment $payment, string $channel): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function verifyWebhook(array $payload, array $headers): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): WebhookEvent;

    public function acknowledgeResponse(): mixed;

    public function isConfigured(): bool;
}
