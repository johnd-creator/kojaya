<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\PaymentGatewayProvider;
use App\Exceptions\PaymentGatewayWebhookVerificationException;
use App\Exceptions\ProviderChargeException;
use App\Models\CooperativePayment;
use App\Models\MemberPaymentIntent;
use App\Support\Money\MinorAmount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function __construct(
        private readonly PaymentGatewayProvider $provider,
        private readonly MemberPaymentIntentStateService $stateService,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyInternalWebhook(array $payload): ?CooperativePayment
    {
        $reference = (string) ($payload['reference'] ?? $payload['gateway_reference'] ?? '');

        if ($reference === '') {
            Log::warning('Payment gateway webhook missing reference');

            return null;
        }

        $payment = CooperativePayment::query()
            ->where('gateway_reference', $reference)
            ->first();

        if (! $payment) {
            Log::warning('Payment gateway webhook payment not found', [
                'gateway_reference' => $reference,
            ]);

            return null;
        }

        $newStatus = strtoupper((string) ($payload['status'] ?? ''));

        if (! MidtransPaymentProvider::isTransitionAllowed($payment->gateway_status, $newStatus)) {
            Log::warning('Payment gateway webhook rejected: invalid status transition', [
                'payment_id' => $payment->id,
                'gateway_reference' => $reference,
                'current_status' => $payment->gateway_status,
                'new_status' => $newStatus,
            ]);

            return $payment;
        }

        $payment->forceFill([
            'gateway_status' => $newStatus,
            'gateway_payload' => $payload,
        ])->save();

        return $payment;
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    public function createCharge(CooperativePayment $payment, string $channel = 'QRIS'): array
    {
        $existingCharge = $this->existingPendingCharge($payment, $channel);

        if ($existingCharge !== null) {
            return $existingCharge;
        }

        if (! $this->provider->isConfigured()) {
            return $this->createChargeInternal($payment, $channel);
        }

        $charge = $this->provider->createCharge($payment, $channel);

        $payment->forceFill([
            'payment_method' => $channel,
            'gateway_provider' => $charge['provider'],
            'gateway_reference' => $charge['reference'],
            'gateway_status' => 'PENDING',
            'gateway_payload' => $this->storedGatewayPayload($charge),
        ])->save();

        return $this->publicChargePayload($charge);
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    public function createIntentCharge(MemberPaymentIntent $intent): array
    {
        $existingCharge = $this->existingPendingIntentCharge($intent);

        if ($existingCharge !== null) {
            return $existingCharge;
        }

        $charge = $this->buildIntentCharge($intent);

        $intent->forceFill([
            'gateway_provider' => $charge['provider'],
            'gateway_reference' => $charge['reference'],
            'gateway_status' => 'PENDING',
            'gateway_payload' => $charge,
        ])->save();

        return $charge;
    }

    /**
     * Build charge data for the intent without persisting to the DB.
     *
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    public function buildIntentCharge(MemberPaymentIntent $intent): array
    {
        if (! $this->provider->isConfigured()) {
            return $this->createIntentChargeInternal($intent);
        }

        $charge = $this->provider->createIntentCharge($intent->loadMissing(['member.user']));

        if (empty($charge['reference'])) {
            throw ProviderChargeException::notCreated(
                'Provider returned charge without a reference (malformed or empty response).'
            );
        }

        return $charge;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function applyWebhook(array $payload, array $headers = []): ?CooperativePayment
    {
        if ($this->provider->isConfigured()) {
            return $this->applyProviderWebhook($payload, $headers);
        }

        return $this->applyInternalWebhook($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     */
    private function applyProviderWebhook(array $payload, array $headers): ?CooperativePayment
    {
        if (! $this->provider->verifyWebhook($payload, $headers)) {
            Log::warning('Payment gateway webhook signature verification failed', [
                'gateway_reference' => $payload['order_id'] ?? $payload['reference'] ?? 'unknown',
            ]);

            throw new PaymentGatewayWebhookVerificationException('Invalid payment gateway webhook signature.');
        }

        $event = $this->provider->parseWebhook($payload);

        $reference = $event->gatewayReference !== '' && $event->gatewayReference !== '0'
            ? $event->gatewayReference
            : ((string) ($payload['reference'] ?? $payload['gateway_reference'] ?? ''));

        if ($reference === '') {
            Log::warning('Payment gateway webhook missing reference');

            throw new PaymentGatewayWebhookVerificationException('Invalid payment gateway webhook signature.');
        }

        $payment = CooperativePayment::query()
            ->where('gateway_reference', $reference)
            ->first();

        if (! $payment) {
            Log::warning('Payment gateway webhook payment not found', [
                'gateway_reference' => $reference,
            ]);

            return null;
        }

        if (! MidtransPaymentProvider::isTransitionAllowed($payment->gateway_status, $event->status)) {
            Log::warning('Payment gateway webhook rejected: invalid status transition', [
                'payment_id' => $payment->id,
                'gateway_reference' => $reference,
                'current_status' => $payment->gateway_status,
                'new_status' => $event->status,
            ]);

            return $payment;
        }

        $payment->forceFill([
            'gateway_status' => $event->status,
            'gateway_payload' => $this->mergeGatewayWebhookPayload($payment->gateway_payload, $event->rawPayload),
        ])->save();

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function applyWebhookToMemberIntent(array $payload, array $headers = []): ?MemberPaymentIntent
    {
        if ($this->provider->isConfigured()) {
            if (! $this->provider->verifyWebhook($payload, $headers)) {
                return null;
            }

            $event = $this->provider->parseWebhook($payload);
            $reference = $event->gatewayReference !== '' && $event->gatewayReference !== '0'
                ? $event->gatewayReference
                : ((string) ($payload['reference'] ?? $payload['gateway_reference'] ?? ''));

            if ($reference === '') {
                return null;
            }

            return $this->stateService->applyGatewayEvent(
                $reference,
                $event->status,
                $event->rawPayload,
                $event->amountMinor,
            );
        }

        $reference = (string) ($payload['reference'] ?? $payload['gateway_reference'] ?? '');

        if ($reference === '') {
            return null;
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        return $this->stateService->applyGatewayEvent($reference, $status, $payload);
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    public function createChargeInternal(CooperativePayment $payment, string $channel = 'QRIS'): array
    {
        $reference = 'PAY-'.Str::upper(Str::random(12));
        $expiresAt = now()->addDay()->toIso8601String();

        $payment->forceFill([
            'payment_method' => $channel,
            'gateway_provider' => 'internal',
            'gateway_reference' => $reference,
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'provider' => 'internal',
                'reference' => $reference,
                'status' => 'PENDING',
                'channel' => $channel,
                'amount' => (float) $payment->amount,
                'checkout_url' => url("/api/payments/{$reference}/checkout"),
                'qr_string' => null,
                'qr_image_url' => $channel === 'QRIS' ? route('api.v1.member.payments.qris-image', $payment, false) : null,
                'expires_at' => $expiresAt,
                'instructions' => [],
                'poll_after_seconds' => 5,
            ],
        ])->save();

        return [
            'provider' => 'internal',
            'reference' => $reference,
            'status' => 'PENDING',
            'channel' => $channel,
            'amount' => (float) $payment->amount,
            'checkout_url' => url("/api/payments/{$reference}/checkout"),
            'qr_image_url' => $channel === 'QRIS' ? route('api.v1.member.payments.qris-image', $payment, false) : null,
            'expires_at' => $expiresAt,
            'instructions' => [],
            'poll_after_seconds' => 5,
        ];
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, expires_at?: string|null, instructions?: array<string, mixed>}
     */
    public function createIntentChargeInternal(MemberPaymentIntent $intent): array
    {
        $attempt = (int) ($intent->charge_attempt ?: 1);
        $reference = sprintf('MPI-%d-%d', $intent->id, $attempt);
        $expiresAt = $intent->expires_at ?? now()->addMinutes(30);

        return [
            'provider' => 'internal',
            'reference' => $reference,
            'status' => 'PENDING',
            'channel' => $intent->channel,
            'amount' => (float) $intent->amount,
            'checkout_url' => url("/api/payments/{$reference}/checkout"),
            'qr_string' => null,
            'expires_at' => $expiresAt->toIso8601String(),
            'instructions' => [],
        ];
    }

    /**
     * Reconcile a member payment intent charge by its provider order ID.
     *
     * For the internal provider, returns null (not found) — allowing safe
     * retry of the same attempt. For the real provider, queries the provider
     * status API.
     *
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string?: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>}|null
     */
    public function reconcileIntentCharge(string $providerOrderId): ?array
    {
        if (! $this->provider->isConfigured()) {
            return null;
        }

        return $this->provider->reconcileIntentCharge($providerOrderId);
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}|null
     */
    private function existingPendingCharge(CooperativePayment $payment, string $channel): ?array
    {
        return $this->existingPendingChargeFromPayload(
            gatewayStatus: $payment->gateway_status,
            payload: $payment->gateway_payload,
            amount: (float) $payment->amount,
            channel: $channel,
        );
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}|null
     */
    private function existingPendingIntentCharge(MemberPaymentIntent $intent): ?array
    {
        if ($intent->expires_at?->isPast() === true) {
            return null;
        }

        return $this->existingPendingChargeFromPayload(
            gatewayStatus: $intent->gateway_status,
            payload: $intent->gateway_payload,
            amount: (float) $intent->amount,
            channel: $intent->channel,
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}|null
     */
    private function existingPendingChargeFromPayload(?string $gatewayStatus, ?array $payload, float $amount, string $channel): ?array
    {
        if ($gatewayStatus !== 'PENDING' || ! is_array($payload)) {
            return null;
        }

        if (is_array($payload['presentation'] ?? null)) {
            $payload = $payload['presentation'];
        }

        if (($payload['channel'] ?? null) !== $channel || empty($payload['reference'])) {
            return null;
        }

        // Exact amount comparison in minor units — no float tolerance
        if (isset($payload['amount']) && ! MinorAmount::equals($payload['amount'], $amount)) {
            return null;
        }

        if (! empty($payload['expires_at'])) {
            try {
                if (CarbonImmutable::parse((string) $payload['expires_at'])->isPast()) {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        // Don't reuse a "hollow" charge whose provider call failed and left no
        // actionable payment artefact (e.g. an earlier "channel not activated"
        // response). Force a fresh charge instead.
        $hasArtefact = ! empty($payload['qr_string'])
            || ! empty($payload['qr_image_url'])
            || ! empty($payload['checkout_url'])
            || ! empty($payload['instructions']['va_number'] ?? null);
        if (! $hasArtefact) {
            return null;
        }

        $instructions = is_array($payload['instructions'] ?? null) ? $payload['instructions'] : [];
        unset($instructions['qr_action_url']);

        return [
            'provider' => (string) ($payload['provider'] ?? 'internal'),
            'reference' => (string) $payload['reference'],
            'status' => (string) ($payload['status'] ?? 'PENDING'),
            'channel' => (string) $payload['channel'],
            'amount' => (float) ($payload['amount'] ?? $amount),
            'checkout_url' => isset($payload['checkout_url']) ? (string) $payload['checkout_url'] : null,
            'qr_image_url' => isset($payload['qr_image_url']) ? (string) $payload['qr_image_url'] : null,
            'expires_at' => isset($payload['expires_at']) ? (string) $payload['expires_at'] : null,
            'instructions' => $instructions,
            'poll_after_seconds' => (int) ($payload['poll_after_seconds'] ?? 5),
        ];
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function storedGatewayPayload(array $charge): array
    {
        $payload = $charge['gateway_payload'] ?? $charge;

        if (is_array($payload)) {
            $payload['presentation'] = $this->publicChargePayload($charge);

            return $payload;
        }

        return $this->publicChargePayload($charge);
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    private function publicChargePayload(array $charge): array
    {
        $instructions = is_array($charge['instructions'] ?? null) ? $charge['instructions'] : [];
        unset($instructions['qr_action_url']);

        return [
            'provider' => (string) ($charge['provider'] ?? 'internal'),
            'reference' => (string) ($charge['reference'] ?? ''),
            'status' => (string) ($charge['status'] ?? 'PENDING'),
            'channel' => (string) ($charge['channel'] ?? 'QRIS'),
            'amount' => (float) ($charge['amount'] ?? 0),
            'checkout_url' => isset($charge['checkout_url']) ? (string) $charge['checkout_url'] : null,
            'qr_image_url' => isset($charge['qr_image_url']) ? (string) $charge['qr_image_url'] : null,
            'expires_at' => isset($charge['expires_at']) ? (string) $charge['expires_at'] : null,
            'instructions' => $instructions,
            'poll_after_seconds' => (int) ($charge['poll_after_seconds'] ?? 5),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $existingPayload
     * @param  array<string, mixed>  $webhookPayload
     * @return array<string, mixed>
     */
    private function mergeGatewayWebhookPayload(?array $existingPayload, array $webhookPayload): array
    {
        $payload = is_array($existingPayload) ? $existingPayload : [];
        $payload['latest_webhook'] = $webhookPayload;

        return $payload;
    }
}
