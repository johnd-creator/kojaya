<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\PaymentReconciliationIncident;
use App\Services\Integrations\MemberPaymentIntentStateService;
use App\Services\Integrations\MemberPaymentSettlementService;
use App\Services\Integrations\PaymentGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RecoverStaleChargeCreating extends Command
{
    protected $signature = 'orders:recover-stale-charges {--minutes=5 : staleness threshold in minutes} {--limit=50 : max intents per run}';

    protected $description = 'Recover member payment intents stuck in CHARGE_CREATING via provider reconciliation (3-phase: lock→provider→apply)';

    public function handle(
        PaymentGatewayService $gateway,
        MemberPaymentIntentStateService $stateService,
        MemberPaymentSettlementService $settlementService,
    ): int {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, min((int) $this->option('limit'), 500));
        $threshold = now()->subMinutes($minutes);

        $candidates = MemberPaymentIntent::query()
            ->where('gateway_status', 'CHARGE_CREATING')
            ->where('updated_at', '<=', $threshold)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $recovered = 0;
        $retried = 0;
        $unknown = 0;

        foreach ($candidates as $intentId) {
            $result = $this->recoverIntent($intentId, $gateway, $stateService, $settlementService);
            match ($result) {
                'recovered' => $recovered++,
                'retried' => $retried++,
                'unknown' => $unknown++,
                default => null,
            };
        }

        $this->info("Recovery: {$recovered} recovered from provider, {$retried} safely retried, {$unknown} still UNKNOWN (reconciliation incident created).");

        return self::SUCCESS;
    }

    /**
     * Three-phase recovery:
     *   Phase A (short tx): lock intent + attempt, validate, claim, snapshot identity
     *   Provider call:      outside any DB transaction
     *   Phase B (short tx): lock, verify claim, apply result per provider status
     *   Phase C:            state service routing for PAID/terminal (separate tx)
     *
     * @return array{action: string, status?: string, reference?: string, amount_minor?: int|null, payload?: array<string, mixed>|null}|null
     */
    private function recoverIntent(
        int $intentId,
        PaymentGatewayService $gateway,
        MemberPaymentIntentStateService $stateService,
        MemberPaymentSettlementService $settlementService,
    ): string {
        // ── Phase A: lock, validate, claim, snapshot ──────────────────
        $snapshot = $this->recoverPhaseA($intentId);

        if ($snapshot === null) {
            return 'skip';
        }

        if ($snapshot['early_result'] ?? null) {
            return $snapshot['early_result'];
        }

        // ── Provider call: OUTSIDE DB transaction ─────────────────────
        $providerOrderId = $snapshot['provider_order_id'];

        $providerCharge = null;
        $reconcileError = null;

        try {
            $providerCharge = $gateway->reconcileIntentCharge($providerOrderId);
        } catch (RuntimeException $exception) {
            $reconcileError = $exception;

            Log::warning('Reconciliation: provider unavailable', [
                'intent_id' => $intentId,
                'error' => $exception->getMessage(),
            ]);
        }

        // ── Phase B: lock, verify claim, apply result ─────────────────
        $postAction = $this->recoverPhaseB($intentId, $snapshot, $providerCharge, $reconcileError);

        // ── Phase C: state service routing for PAID/terminal ──────────
        if (isset($postAction['action']) && in_array($postAction['action'], ['paid', 'terminal'], true)) {
            $this->executeStateAction($intentId, $postAction, $stateService, $settlementService);
        }

        return match ($postAction['action'] ?? 'skip') {
            'recovered' => 'recovered',
            'retried' => 'retried',
            'paid', 'terminal' => 'recovered',
            'unknown', 'error' => 'unknown',
            default => 'skip',
        };
    }

    /**
     * Phase A: short transaction to lock, validate, claim, and snapshot.
     *
     * @return array<string, mixed>|null
     */
    private function recoverPhaseA(int $intentId): ?array
    {
        return DB::transaction(function () use ($intentId): ?array {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return null;
            }

            $attempt = (int) $locked->charge_attempt;

            $attemptRecord = MemberPaymentChargeAttempt::query()
                ->where('member_payment_intent_id', $locked->id)
                ->where('attempt', $attempt)
                ->lockForUpdate()
                ->first();

            if (! $attemptRecord) {
                // No attempt record — safe to reset since no provider call was made
                $locked->forceFill(['gateway_status' => 'PENDING'])->save();

                return ['early_result' => 'recovered'];
            }

            if ($attemptRecord->provider_reference) {
                // Already confirmed with provider reference — persist as PENDING
                $locked->forceFill([
                    'gateway_reference' => $attemptRecord->provider_reference,
                    'gateway_status' => 'PENDING',
                    'gateway_payload' => $attemptRecord->response_payload ?? [],
                ])->save();

                return ['early_result' => 'recovered'];
            }

            $providerOrderId = $attemptRecord->provider_order_id;

            if ($providerOrderId === null) {
                // No stable provider order ID — cannot reconcile safely
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                ])->save();

                $this->createIncident($locked, $attempt, 'reconciliation_unknown', 'No stable provider order ID for reconciliation.');

                return ['early_result' => 'unknown'];
            }

            // Claim: mark attempt SENT so concurrent recovery workers skip it
            $attemptRecord->forceFill([
                'state' => MemberPaymentChargeAttempt::STATE_SENT,
            ])->save();

            return [
                'attempt' => $attempt,
                'provider_order_id' => $providerOrderId,
                'idempotency_key' => $attemptRecord->idempotency_key,
            ];
        });
    }

    /**
     * Phase B: short transaction to verify claim and apply result.
     *
     * Stale-result fencing: if the intent is no longer CHARGE_CREATING or the
     * attempt is no longer SENT (our claim), the result is discarded.
     *
     * @param  array<string, mixed>|null  $providerCharge
     */
    private function recoverPhaseB(int $intentId, array $snapshot, ?array $providerCharge, ?RuntimeException $reconcileError): array
    {
        $attempt = (int) $snapshot['attempt'];

        return DB::transaction(function () use ($intentId, $attempt, $providerCharge, $reconcileError): array {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            // Stale-result fencing: verify intent is still CHARGE_CREATING
            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return ['action' => 'skip'];
            }

            if ((int) $locked->charge_attempt !== $attempt) {
                return ['action' => 'skip'];
            }

            $attemptRecord = MemberPaymentChargeAttempt::query()
                ->where('member_payment_intent_id', $locked->id)
                ->where('attempt', $attempt)
                ->lockForUpdate()
                ->first();

            if (! $attemptRecord) {
                return ['action' => 'skip'];
            }

            // Verify our claim is still valid (attempt must be SENT)
            if ($attemptRecord->state !== MemberPaymentChargeAttempt::STATE_SENT) {
                return ['action' => 'skip'];
            }

            // ── Provider unavailable ──────────────────────────────────
            if ($reconcileError !== null) {
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                ])->save();

                $this->createIncident($locked, $attempt, 'provider_unavailable', $reconcileError->getMessage());

                return ['action' => 'unknown'];
            }

            // ── Provider says not found → safe retry same attempt ─────
            if ($providerCharge === null) {
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
                    'completed_at' => null,
                ])->save();

                $locked->forceFill(['gateway_status' => 'PENDING'])->save();

                Log::info('Recovery: provider confirms no charge, intent reset to PENDING for safe retry', [
                    'intent_id' => $locked->id,
                    'attempt' => $attempt,
                ]);

                return ['action' => 'retried'];
            }

            $status = strtoupper((string) ($providerCharge['status'] ?? 'UNKNOWN'));
            $reference = (string) ($providerCharge['reference'] ?? $snapshot['provider_order_id']);

            // ── Unknown provider status → incident + blocked ──────────
            if ($status === 'UNKNOWN') {
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                    'response_payload' => $providerCharge,
                ])->save();

                $this->createIncident($locked, $attempt, 'reconciliation_unknown_status', 'Provider returned unknown status.');

                return ['action' => 'unknown'];
            }

            // For all known statuses: attach the charge reference first,
            // mark attempt CONFIRMED, set gateway_status to PENDING. Then
            // Phase C routes PAID/terminal through the state service.
            $amountMinor = null;
            $amount = (float) ($providerCharge['amount'] ?? 0);
            if ($amount > 0) {
                $amountMinor = (int) bcmul((string) $amount, '100', 0);
            }

            $attemptRecord->forceFill([
                'state' => MemberPaymentChargeAttempt::STATE_CONFIRMED,
                'provider_reference' => $reference,
                'response_payload' => $providerCharge,
                'completed_at' => now(),
            ])->save();

            $locked->forceFill([
                'gateway_reference' => $reference,
                'gateway_status' => 'PENDING',
                'gateway_payload' => $providerCharge,
            ])->save();

            if ($status === 'PENDING') {
                Log::info('Recovery: provider charge found, status PENDING', [
                    'intent_id' => $locked->id,
                    'attempt' => $attempt,
                    'provider_reference' => $reference,
                ]);

                return ['action' => 'recovered'];
            }

            // PAID, EXPIRED, CANCELLED, FAILED, DENIED → route through state service
            Log::info('Recovery: provider charge found, routing through state service', [
                'intent_id' => $locked->id,
                'attempt' => $attempt,
                'provider_reference' => $reference,
                'status' => $status,
            ]);

            return [
                'action' => in_array($status, ['EXPIRED', 'CANCELLED', 'FAILED', 'DENIED'], true) ? 'terminal' : 'paid',
                'status' => $status,
                'reference' => $reference,
                'amount_minor' => $amountMinor,
                'payload' => $providerCharge,
            ];
        });
    }

    /**
     * Phase C: route PAID/terminal statuses through the state service and
     * settlement (in a separate transaction, outside Phase B's lock scope).
     *
     * @param  array<string, mixed>  $action
     */
    private function executeStateAction(
        int $intentId,
        array $action,
        MemberPaymentIntentStateService $stateService,
        MemberPaymentSettlementService $settlementService,
    ): void {
        $status = (string) ($action['status'] ?? 'PAID');
        $reference = (string) ($action['reference'] ?? '');
        $payload = $action['payload'] ?? [];
        $amountMinor = $action['amount_minor'] ?? null;

        if ($reference === '') {
            Log::error('Recovery state action: missing gateway reference', ['intent_id' => $intentId]);

            return;
        }

        $intent = $stateService->applyGatewayEvent(
            $reference,
            $status,
            $payload,
            $amountMinor,
        );

        if ($status === 'PAID' && $intent !== null && $intent->gateway_status === 'PAID' && $intent->settled_at === null) {
            $settlementService->settle($intent);
        }
    }

    private function createIncident(MemberPaymentIntent $intent, int $attempt, string $type, string $detail): void
    {
        $fingerprint = md5($intent->id.'|'.$attempt.'|'.$type);

        PaymentReconciliationIncident::query()->firstOrCreate(
            ['deduplication_key' => $fingerprint],
            [
                'member_payment_intent_id' => $intent->id,
                'gateway_reference' => $intent->gateway_reference,
                'incident_type' => $type,
                'status' => PaymentReconciliationIncident::STATUS_OPEN,
                'webhook_payload' => ['attempt' => $attempt, 'detail' => $detail],
                'incident_at' => now(),
            ],
        );

        Log::warning('Recovery: reconciliation incident created', [
            'intent_id' => $intent->id,
            'attempt' => $attempt,
            'type' => $type,
        ]);
    }
}
