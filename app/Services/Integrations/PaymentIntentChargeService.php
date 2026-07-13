<?php

namespace App\Services\Integrations;

use App\Enums\ChargeCommitResult;
use App\Enums\ProviderChargeOutcome;
use App\Exceptions\ProviderChargeException;
use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\PaymentReconciliationIncident;
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
     *   Transaction A: lock intent, set CHARGE_CREATING, create attempt record
     *                  with stable provider order ID + idempotency key, commit
     *   Provider call: outside the DB transaction
     *   Transaction B: lock intent, verify charge_attempt===expectedAttempt,
     *                  save charge, return typed result
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

        if (isset($phaseA['reconciliation_required'])) {
            return $this->reconciliationRequiredResponse($intent);
        }

        /** @var int $attempt */
        $attempt = $phaseA['attempt'];
        /** @var string $idempotencyKey */
        $idempotencyKey = $phaseA['idempotency_key'];
        /** @var string $providerOrderId */
        $providerOrderId = $phaseA['provider_order_id'];

        // Mark attempt as SENT before provider call
        $this->markAttemptSent($intent->id, $attempt);

        try {
            $charge = $this->gateway->buildIntentCharge($intent->refresh());
        } catch (ProviderChargeException $exception) {
            $this->handleProviderChargeException($intent->id, $attempt, $exception);

            throw $exception;
        } catch (RuntimeException $exception) {
            // Unclassified RuntimeException from unknown source — treat as Unknown
            $this->handleProviderChargeException(
                $intent->id,
                $attempt,
                ProviderChargeException::unknown($exception->getMessage(), null, $exception),
            );

            throw $exception;
        }

        $result = $this->commitTransactionB($intent->id, $attempt, $charge);

        if ($result === ChargeCommitResult::Committed) {
            $this->markAttemptConfirmed($intent->id, $attempt, $charge);

            return $charge;
        }

        // Stale or rejected — persist provider evidence on the attempt
        // and create a reconciliation incident for orphaned charges.
        if ($charge['reference'] ?? null) {
            $this->markAttemptOrphaned($intent->id, $attempt, $charge);
            $this->createOrphanIncident($intent->id, $attempt, $charge, $result);
        }

        return $this->reconciliationRequiredResponse($intent->refresh());
    }

    /**
     * @return array{reusable?: array<string, mixed>, preparing?: bool, reconciliation_required?: bool, attempt?: int, idempotency_key?: string, provider_order_id?: string}
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

            // PENDING with a PREPARING attempt from a previous safe retry:
            // reuse the same attempt identity (attempt number, idempotency
            // key, provider order ID) instead of creating a new attempt.
            if ($status === 'PENDING' && $locked->charge_attempt > 0) {
                $reusableAttempt = MemberPaymentChargeAttempt::query()
                    ->where('member_payment_intent_id', $locked->id)
                    ->where('attempt', (int) $locked->charge_attempt)
                    ->where('state', MemberPaymentChargeAttempt::STATE_PREPARING)
                    ->lockForUpdate()
                    ->first();

                if ($reusableAttempt) {
                    $locked->forceFill([
                        'gateway_status' => 'CHARGE_CREATING',
                    ])->save();

                    return [
                        'attempt' => (int) $reusableAttempt->attempt,
                        'idempotency_key' => $reusableAttempt->idempotency_key,
                        'provider_order_id' => $reusableAttempt->provider_order_id,
                    ];
                }
            }

            if ($status === 'CHARGE_CREATING') {
                // If attempt is already UNKNOWN (from a provider failure),
                // block immediately — no need to wait for staleness.
                $attemptState = MemberPaymentChargeAttempt::query()
                    ->where('member_payment_intent_id', $locked->id)
                    ->where('attempt', (int) $locked->charge_attempt)
                    ->value('state');

                if ($attemptState === MemberPaymentChargeAttempt::STATE_UNKNOWN) {
                    return ['reconciliation_required' => true];
                }

                $staleAttempt = $this->checkStaleChargeCreating($locked);
                if ($staleAttempt === null) {
                    return ['preparing' => true];
                }

                // Stale CHARGE_CREATING: mark attempt UNKNOWN but do NOT reset
                // intent to PENDING. The intent stays blocked until recovery
                // reconciliation completes. This prevents creating a new attempt.
                $this->markAttemptUnknown($locked->id, (int) $locked->charge_attempt);

                Log::warning('Stale CHARGE_CREATING detected, attempt marked UNKNOWN, intent stays blocked', [
                    'intent_id' => $locked->id,
                    'attempt' => $staleAttempt,
                ]);

                return ['reconciliation_required' => true];
            }

            $nextAttempt = ($locked->charge_attempt ?? 0) + 1;
            $idempotencyKey = sprintf('member-intent:%s:%s', $locked->id, $nextAttempt);
            $providerOrderId = sprintf('KOJ-MPI-%d-%d', $locked->id, $nextAttempt);

            $requestPayload = [
                'intent_id' => $locked->id,
                'amount' => (float) $locked->amount,
                'channel' => $locked->channel,
                'provider_order_id' => $providerOrderId,
                'idempotency_key' => $idempotencyKey,
            ];

            $locked->forceFill([
                'gateway_status' => 'CHARGE_CREATING',
                'charge_attempt' => $nextAttempt,
            ])->save();

            MemberPaymentChargeAttempt::query()->create([
                'member_payment_intent_id' => $locked->id,
                'attempt' => $nextAttempt,
                'idempotency_key' => $idempotencyKey,
                'provider_order_id' => $providerOrderId,
                'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
                'request_payload' => $requestPayload,
                'started_at' => now(),
            ]);

            return [
                'attempt' => $nextAttempt,
                'idempotency_key' => $idempotencyKey,
                'provider_order_id' => $providerOrderId,
            ];
        });
    }

    /**
     * Transaction B: verify charge_attempt === expectedAttempt before saving.
     *
     * @param  array<string, mixed>  $charge
     */
    private function commitTransactionB(int $intentId, int $expectedAttempt, array $charge): ChargeCommitResult
    {
        return DB::transaction(function () use ($intentId, $expectedAttempt, $charge): ChargeCommitResult {
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

                return ChargeCommitResult::StaleAttempt;
            }

            $status = strtoupper((string) $locked->gateway_status);

            if (in_array($status, ['PAID', 'EXPIRED', 'CANCELLED', 'DENIED'], true)) {
                return ChargeCommitResult::Terminal;
            }

            if ($status !== 'CHARGE_CREATING') {
                return ChargeCommitResult::StaleAttempt;
            }

            // Verify reservation still RESERVED
            if ($locked->reservationStatus()->value !== 'RESERVED') {
                return ChargeCommitResult::InvalidReservation;
            }

            if ($locked->expires_at?->isPast() === true) {
                return ChargeCommitResult::Expired;
            }

            $locked->forceFill([
                'gateway_provider' => $charge['provider'] ?? null,
                'gateway_reference' => $charge['reference'] ?? null,
                'gateway_status' => 'PENDING',
                'gateway_payload' => $charge,
            ])->save();

            return ChargeCommitResult::Committed;
        });
    }

    /**
     * Apply the correct recovery behaviour based on the classified provider
     * outcome.
     *
     * Unknown: intent stays CHARGE_CREATING, attempt → UNKNOWN, create incident.
     * DefinitiveNotCreated: attempt → PREPARING, intent → PENDING (safe retry
     *   of the same attempt via beginTransactionA reuse path).
     * DefinitiveRejected: attempt → FAILED, intent → PENDING (new attempt).
     */
    private function handleProviderChargeException(int $intentId, int $expectedAttempt, ProviderChargeException $exception): void
    {
        $outcome = $exception->outcome;

        DB::transaction(function () use ($intentId, $expectedAttempt, $exception, $outcome): void {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            // Attempt fencing: only handle for our attempt
            if ((int) $locked->charge_attempt !== $expectedAttempt) {
                return;
            }

            if ($outcome === ProviderChargeOutcome::Unknown) {
                // Keep intent in CHARGE_CREATING — do NOT reset to PENDING.
                // The charge may have been created; block until reconciliation.
                $this->markAttemptUnknown($locked->id, $expectedAttempt);

                $this->createChargeFailureIncident($locked, $expectedAttempt, $exception, 'charge_unknown');

                $locked->forceFill([
                    'gateway_payload' => array_merge(is_array($locked->gateway_payload) ? $locked->gateway_payload : [], [
                        'charge_failure' => [
                            'attempt' => $expectedAttempt,
                            'outcome' => $outcome->value,
                            'error' => $exception->getMessage(),
                            'at' => now()->toISOString(),
                        ],
                    ]),
                ])->save();

                return;
            }

            // DefinitiveNotCreated or DefinitiveRejected: safe to allow retry.
            $status = strtoupper((string) $locked->gateway_status);

            if ($status !== 'CHARGE_CREATING') {
                return;
            }

            if ($outcome === ProviderChargeOutcome::DefinitiveNotCreated) {
                // Mark attempt PREPARING so beginTransactionA reuses it for
                // safe retry with the same identity. Reset intent to PENDING.
                $this->resetAttemptForRetry($locked->id, $expectedAttempt);

                $locked->forceFill(['gateway_status' => 'PENDING'])->save();
            } else {
                // DefinitiveRejected: mark attempt FAILED, reset intent to PENDING.
                MemberPaymentChargeAttempt::query()
                    ->where('member_payment_intent_id', $intentId)
                    ->where('attempt', $expectedAttempt)
                    ->update([
                        'state' => MemberPaymentChargeAttempt::STATE_FAILED,
                        'completed_at' => now(),
                        'response_payload' => ['error' => $exception->getMessage()],
                    ]);

                $locked->forceFill([
                    'gateway_status' => 'PENDING',
                    'gateway_payload' => array_merge(is_array($locked->gateway_payload) ? $locked->gateway_payload : [], [
                        'charge_failure' => [
                            'attempt' => $expectedAttempt,
                            'outcome' => $outcome->value,
                            'error' => $exception->getMessage(),
                            'at' => now()->toISOString(),
                        ],
                    ]),
                ])->save();
            }
        });

        Log::error('Payment intent charge creation failed', [
            'intent_id' => $intentId,
            'attempt' => $expectedAttempt,
            'outcome' => $outcome->value,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Reset an attempt back to PREPARING so that beginTransactionA can reuse
     * the same attempt number, idempotency key, and provider order ID.
     */
    private function resetAttemptForRetry(int $intentId, int $attempt): void
    {
        MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intentId)
            ->where('attempt', $attempt)
            ->update([
                'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
                'provider_reference' => null,
                'response_payload' => null,
                'completed_at' => null,
            ]);
    }

    /**
     * Create a reconciliation incident for a failed charge creation.
     */
    private function createChargeFailureIncident(
        MemberPaymentIntent $intent,
        int $attempt,
        ProviderChargeException $exception,
        string $type,
    ): void {
        $fingerprint = md5($intent->id.'|'.$attempt.'|'.$type);

        PaymentReconciliationIncident::query()->firstOrCreate(
            ['deduplication_key' => $fingerprint],
            [
                'member_payment_intent_id' => $intent->id,
                'gateway_reference' => $intent->gateway_reference,
                'incident_type' => $type,
                'status' => PaymentReconciliationIncident::STATUS_OPEN,
                'webhook_payload' => [
                    'attempt' => $attempt,
                    'outcome' => $exception->outcome->value,
                    'error' => $exception->getMessage(),
                    'http_status' => $exception->httpStatus,
                ],
                'incident_at' => now(),
            ],
        );
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

    private function markAttemptSent(int $intentId, int $attempt): void
    {
        MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intentId)
            ->where('attempt', $attempt)
            ->update([
                'state' => MemberPaymentChargeAttempt::STATE_SENT,
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
     * @param  array<string, mixed>  $charge
     */
    private function markAttemptOrphaned(int $intentId, int $attempt, array $charge): void
    {
        MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intentId)
            ->where('attempt', $attempt)
            ->update([
                'state' => MemberPaymentChargeAttempt::STATE_ORPHANED,
                'provider_reference' => $charge['reference'] ?? null,
                'response_payload' => $charge,
                'completed_at' => now(),
            ]);
    }

    /**
     * Create a reconciliation incident for a provider charge that was
     * created but cannot be attached to the authoritative intent.
     *
     * @param  array<string, mixed>  $charge
     */
    private function createOrphanIncident(int $intentId, int $attempt, array $charge, ChargeCommitResult $result): void
    {
        $fingerprint = md5($intentId.'|'.$attempt.'|'.($charge['reference'] ?? ''));

        PaymentReconciliationIncident::query()->firstOrCreate(
            [
                'deduplication_key' => $fingerprint,
            ],
            [
                'member_payment_intent_id' => $intentId,
                'gateway_reference' => $charge['reference'] ?? null,
                'incident_type' => 'orphaned_charge',
                'status' => PaymentReconciliationIncident::STATUS_OPEN,
                'provider_status' => $charge['status'] ?? null,
                'provider_reference' => $charge['reference'] ?? null,
                'webhook_payload' => ['charge' => $charge, 'reason' => $result->value],
                'incident_at' => now(),
            ],
        );

        Log::error('Orphaned provider charge detected, incident created', [
            'intent_id' => $intentId,
            'attempt' => $attempt,
            'provider_reference' => $charge['reference'] ?? null,
            'reason' => $result->value,
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

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: null, poll_after_seconds: int}
     */
    private function reconciliationRequiredResponse(MemberPaymentIntent $intent): array
    {
        return [
            'provider' => (string) ($intent->gateway_provider ?? 'internal'),
            'reference' => (string) ($intent->gateway_reference ?? ''),
            'status' => 'RECONCILIATION_REQUIRED',
            'channel' => $intent->channel,
            'amount' => (float) $intent->amount,
            'checkout_url' => null,
            'poll_after_seconds' => 10,
        ];
    }
}
