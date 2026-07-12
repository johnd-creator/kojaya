<?php

namespace App\Services\Integrations;

use App\Models\MemberPaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentIntentChargeService
{
    private const STALE_CHARGE_MINUTES = 5;

    public function __construct(
        private readonly PaymentGatewayService $gateway,
    ) {}

    /**
     * Ensure exactly one active provider charge exists for the intent.
     *
     * Uses a two-phase locking strategy:
     *   Transaction A: lock intent, set CHARGE_CREATING, commit
     *   Provider call: outside the DB transaction
     *   Transaction B: lock intent, verify, save charge, set PENDING
     *
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string?: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}
     */
    public function ensureCharge(MemberPaymentIntent $intent): array
    {
        $phaseA = $this->beginTransactionA($intent);

        if (isset($phaseA['reusable'])) {
            /** @var array<string, mixed> $phaseA */
            return $phaseA['reusable'];
        }

        if (isset($phaseA['preparing'])) {
            return $this->preparingResponse($intent);
        }

        /** @var int $attempt */
        $attempt = $phaseA['attempt'];

        try {
            $charge = $this->gateway->buildIntentCharge($intent->refresh());
        } catch (RuntimeException $exception) {
            $this->handleChargeFailure($intent, $attempt, $exception);

            throw $exception;
        }

        $this->commitTransactionB($intent, $charge);

        return $charge;
    }

    /**
     * @return array{reusable?: array<string, mixed>, preparing?: bool, attempt?: int}
     */
    private function beginTransactionA(MemberPaymentIntent $intent): array
    {
        return DB::transaction(function () use ($intent): array {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            $status = strtoupper((string) $locked->gateway_status);

            if (in_array($status, ['PAID', 'EXPIRED', 'CANCELLED', 'DENIED'], true)) {
                throw new RuntimeException(
                    "Cannot create charge: intent {$locked->id} is in terminal gateway state {$status}."
                );
            }

            if ($locked->settled_at !== null) {
                throw new RuntimeException(
                    "Cannot create charge: intent {$locked->id} is already settled."
                );
            }

            $existing = $this->extractReusableCharge($locked);
            if ($existing !== null) {
                return ['reusable' => $existing];
            }

            if ($status === 'CHARGE_CREATING') {
                if ($this->isStaleChargeCreating($locked)) {
                    $locked->forceFill(['gateway_status' => 'PENDING'])->save();
                } else {
                    return ['preparing' => true];
                }
            }

            $nextAttempt = ($locked->charge_attempt ?? 0) + 1;

            $locked->forceFill([
                'gateway_status' => 'CHARGE_CREATING',
                'charge_attempt' => $nextAttempt,
            ])->save();

            return ['attempt' => $nextAttempt];
        });
    }

    /**
     * @param  array<string, mixed>  $charge
     */
    private function commitTransactionB(MemberPaymentIntent $intent, array $charge): void
    {
        DB::transaction(function () use ($intent, $charge): void {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return;
            }

            $locked->forceFill([
                'gateway_provider' => $charge['provider'] ?? null,
                'gateway_reference' => $charge['reference'] ?? null,
                'gateway_status' => 'PENDING',
                'gateway_payload' => $charge,
            ])->save();
        });
    }

    private function handleChargeFailure(MemberPaymentIntent $intent, int $attempt, RuntimeException $exception): void
    {
        DB::transaction(function () use ($intent, $attempt, $exception): void {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return;
            }

            $locked->forceFill([
                'gateway_status' => 'PENDING',
                'gateway_payload' => array_merge(is_array($locked->gateway_payload) ? $locked->gateway_payload : [], [
                    'charge_failure' => [
                        'attempt' => $attempt,
                        'error' => $exception->getMessage(),
                        'at' => now()->toISOString(),
                    ],
                ]),
            ])->save();
        });

        Log::error('Payment intent charge creation failed', [
            'intent_id' => $intent->id,
            'attempt' => $attempt,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractReusableCharge(MemberPaymentIntent $intent): ?array
    {
        if (strtoupper((string) $intent->gateway_status) !== 'PENDING') {
            return null;
        }

        if ($intent->expires_at?->isPast() === true) {
            return null;
        }

        $payload = $intent->gateway_payload;
        if (! is_array($payload)) {
            return null;
        }

        if (is_array($payload['presentation'] ?? null)) {
            $payload = $payload['presentation'];
        }

        if (empty($payload['reference'])) {
            return null;
        }

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
            'channel' => (string) ($payload['channel'] ?? $intent->channel),
            'amount' => (float) ($payload['amount'] ?? $intent->amount),
            'checkout_url' => isset($payload['checkout_url']) ? (string) $payload['checkout_url'] : null,
            'qr_image_url' => isset($payload['qr_image_url']) ? (string) $payload['qr_image_url'] : null,
            'expires_at' => isset($payload['expires_at']) ? (string) $payload['expires_at'] : null,
            'instructions' => $instructions,
            'poll_after_seconds' => (int) ($payload['poll_after_seconds'] ?? 5),
        ];
    }

    private function isStaleChargeCreating(MemberPaymentIntent $intent): bool
    {
        return $intent->updated_at !== null
            && $intent->updated_at->addMinutes(self::STALE_CHARGE_MINUTES)->isPast();
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: null, poll_after_seconds: int}
     */
    private function preparingResponse(MemberPaymentIntent $intent): array
    {
        return [
            'provider' => (string) ($intent->gateway_provider ?? 'internal'),
            'reference' => (string) ($intent->gateway_reference ?? ''),
            'status' => 'PREPARING',
            'channel' => $intent->channel,
            'amount' => (float) $intent->amount,
            'checkout_url' => null,
            'poll_after_seconds' => 2,
        ];
    }
}
