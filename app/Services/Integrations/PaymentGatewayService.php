<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\PaymentGatewayProvider;
use App\Models\CooperativePayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function __construct(private readonly PaymentGatewayProvider $provider)
    {
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
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null}
     */
    public function createCharge(CooperativePayment $payment, string $channel = 'QRIS'): array
    {
        if (! $this->provider->isConfigured()) {
            return $this->createChargeInternal($payment, $channel);
        }

        $charge = $this->provider->createCharge($payment, $channel);

        $payment->forceFill([
            'payment_method' => $channel,
            'gateway_provider' => $charge['provider'],
            'gateway_reference' => $charge['reference'],
            'gateway_status' => 'PENDING',
            'gateway_payload' => $charge,
        ])->save();

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

            return null;
        }

        $event = $this->provider->parseWebhook($payload);

        $reference = $event->gatewayReference !== '' && $event->gatewayReference !== '0'
            ? $event->gatewayReference
            : ((string) ($payload['reference'] ?? $payload['gateway_reference'] ?? ''));

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
            'gateway_payload' => $event->rawPayload,
        ])->save();

        return $payment;
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null}
     */
    public function createChargeInternal(CooperativePayment $payment, string $channel = 'QRIS'): array
    {
        $reference = 'PAY-'.Str::upper(Str::random(12));

        $payment->forceFill([
            'payment_method' => $channel,
            'gateway_provider' => 'internal',
            'gateway_reference' => $reference,
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'channel' => $channel,
                'amount' => (float) $payment->amount,
                'expires_at' => now()->addDay()->toIso8601String(),
            ],
        ])->save();

        return [
            'provider' => 'internal',
            'reference' => $reference,
            'status' => 'PENDING',
            'channel' => $channel,
            'amount' => (float) $payment->amount,
            'checkout_url' => url("/api/payments/{$reference}/checkout"),
            'qr_string' => null,
        ];
    }
}
