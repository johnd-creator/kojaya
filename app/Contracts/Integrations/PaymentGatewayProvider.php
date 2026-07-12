<?php

namespace App\Contracts\Integrations;

use App\Models\CooperativePayment;
use App\Models\MemberPaymentIntent;
use App\Services\Integrations\WebhookEvent;

interface PaymentGatewayProvider
{
    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int, gateway_payload?: array<string, mixed>}
     */
    public function createCharge(CooperativePayment $payment, string $channel): array;

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, expires_at?: string|null, instructions?: array<string, mixed>}
     */
    public function createIntentCharge(MemberPaymentIntent $intent): array;

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

    /**
     * Reconcile a charge by its provider order ID / reference.
     *
     * Returns the charge data if the provider confirms the charge exists,
     * null if the provider authoritatively says it does not exist,
     * or throws on ambiguous/unavailable results.
     *
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string?: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>}|null
     */
    public function reconcileIntentCharge(string $providerOrderId): ?array;
}
