<?php

namespace App\Services\Integrations;

use App\Models\CooperativePayment;

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

    /**
     * Build response acknowledging receipt to the gateway.
     */
    public function acknowledgeResponse(): mixed;

    public function isConfigured(): bool;
}
