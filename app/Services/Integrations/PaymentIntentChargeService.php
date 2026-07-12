<?php

namespace App\Services\Integrations;

use App\Models\MemberPaymentChargeAttempt;
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
     * Two-phase locking with attempt fencing:
     *   Transaction A: lock intent, set CHARGE_CREATING, create attempt record, commit
     *   Provider call: outside the DB transaction
     *   Transaction B: lock intent, verify charge_attempt===expectedAttempt, save charge
     *
     * @return array<string, mixed>
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
        /** @var string $idempotencyKey */
        $idempotencyKey = $phaseA['idempotency_key'];

        try {
            $charge = $this->gateway->buildIntentCharge($intent->refresh());
        } catch (RuntimeException $exception) {
            $this->handleChargeFailure($intent->id, $attempt, $exception);

            throw $exception;
        }

        $this->commitTransactionB($intent->id, $attempt, $charge);

        $this->markAttemptConfirmed($intent->id, $attempt, $charge);

        return $charge;
    }

    /**
     * @return array{reusable?: array<string, mixed>, preparing?: bool, attempt?: int, idempotency_key?: string}
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
                $staleAttempt = $this->checkStaleChargeCreating($locked);
                if ($staleAttempt === null) {
                    return ['preparing' => true];
                }

                // Stale: mark old attempt UNKNOWN, do NOT blindly reset
                $this->markAttemptUnknown($locked->id, (int) $locked->charge_attempt);
                $locked->forceFill([
                    'gateway_status' => 'PENDING',
                ])->save();
            }

            $nextAttempt = ($locked->charge_attempt ?? 0) + 1;
            $idempotencyKey = sprintf('member-intent:%s:%s', $locked->id, $nextAttempt);

            $locked->forceFill([
                'gateway_status' => 'CHARGE_CREATING',
                'charge_attempt' => $nextAttempt,
            ])->save();

            MemberPaymentChargeAttempt::query()->create([
                'member_payment_intent_id' => $locked->id,
                'attempt' => $nextAttempt,
                'idempotency_key' => $idempotencyKey,
                'provider_order_id' => null,
                'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
                'started_at' => now(),
            ]);

            return [
                'attempt' => $nextAttempt,
                'idempotency_key' => $idempotencyKey,
            ];
        });
    }

    /**
     * Transaction B: verify charge_attempt === expectedAttempt before saving.
     *
     * @param  array<string, mixed>  $charge
     */
    private function commitTransactionB(int $intentId, int $expectedAttempt, array $charge): void
    {
        DB::transaction(function () use ($intentId, $expectedAttempt, $charge): void {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            // Attempt fencing: only commit if this is still our attempt
            if ((int) $locked->charge_attempt !== $expectedAttempt) {
                Log::warning('Charge attempt fencing: stale attempt response discarded', [
                    'intent_id' => $intentId,
                    'expected_attempt' => $expectedAttempt,
                    'actual_attempt' => $locked->charge_attempt,
                ]);

                return;
            }

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return;
            }

            // Verify reservation still RESERVED
            if ($locked->reservationStatus()->value !== 'RESERVED') {
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

    private function handleChargeFailure(int $intentId, int $expectedAttempt, RuntimeException $exception): void
    {
        DB::transaction(function () use ($intentId, $expectedAttempt, $exception): void {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            // Attempt fencing: only handle failure for our attempt
            if ((int) $locked->charge_attempt !== $expectedAttempt) {
                return;
            }

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return;
            }

            $locked->forceFill([
                'gateway_status' => 'PENDING',
                'gateway_payload' => array_merge(is_array($locked->gateway_payload) ? $locked->gateway_payload : [], [
                    'charge_failure' => [
                        'attempt' => $expectedAttempt,
                        'error' => $exception->getMessage(),
                        'at' => now()->toISOString(),
                    ],
                ]),
            ])->save();

            MemberPaymentChargeAttempt::query()
                ->where('member_payment_intent_id', $intentId)
                ->where('attempt', $expectedAttempt)
                ->update([
                    'state' => MemberPaymentChargeAttempt::STATE_FAILED,
                    'completed_at' => now(),
                    'response_payload' => ['error' => $exception->getMessage()],
                ]);
        });

        Log::error('Payment intent charge creation failed', [
            'intent_id' => $intentId,
            'attempt' => $expectedAttempt,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $charge
     */
    private function markAttemptConfirmed(int $intentId, int $attempt, array $charge): void
    {
        MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intentId)
            ->where('attempt', $attempt)
            ->update([
                'state' => MemberPaymentChargeAttempt::STATE_CONFIRMED,
                'provider_reference' => $charge['reference'] ?? null,
                'response_payload' => $charge,
                'completed_at' => now(),
            ]);
    }

    private function markAttemptUnknown(int $intentId, int $attempt): void
    {
        MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intentId)
            ->where('attempt', $attempt)
            ->update([
                'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                'completed_at' => now(),
            ]);
    }

    /**
     * Check if the current CHARGE_CREATING is stale.
     * Returns the attempt number if stale, null if still fresh.
     */
    private function checkStaleChargeCreating(MemberPaymentIntent $intent): ?int
    {
        if ($intent->updated_at === null) {
            return null;
        }

        if ($intent->updated_at->addMinutes(self::STALE_CHARGE_MINUTES)->isFuture()) {
            return null;
        }

        return (int) $intent->charge_attempt;
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
