<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\PaymentReconciliationIncident;
use App\Services\Integrations\PaymentGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RecoverStaleChargeCreating extends Command
{
    protected $signature = 'orders:recover-stale-charges {--minutes=5 : staleness threshold in minutes} {--limit=50 : max intents per run}';

    protected $description = 'Recover member payment intents stuck in CHARGE_CREATING via provider reconciliation (never blindly resets to PENDING)';

    public function handle(PaymentGatewayService $gateway): int
    {
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
            $result = $this->recoverIntent($intentId, $gateway);
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

    private function recoverIntent(int $intentId, PaymentGatewayService $gateway): string
    {
        return DB::transaction(function () use ($intentId, $gateway): string {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return 'skip';
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

                return 'recovered';
            }

            // If attempt already confirmed with provider reference, persist it
            if ($attemptRecord->provider_reference) {
                $locked->forceFill([
                    'gateway_reference' => $attemptRecord->provider_reference,
                    'gateway_status' => 'PENDING',
                    'gateway_payload' => $attemptRecord->response_payload ?? [],
                ])->save();

                return 'recovered';
            }

            // Query provider using stable provider order ID
            $providerOrderId = $attemptRecord->provider_order_id;
            if ($providerOrderId === null) {
                // No stable provider order ID — cannot reconcile safely.
                // Mark UNKNOWN and create incident.
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                ])->save();

                $this->createIncident($locked, $attempt, 'reconciliation_unknown', 'No stable provider order ID for reconciliation.');

                return 'unknown';
            }

            try {
                $providerCharge = $gateway->reconcileIntentCharge($providerOrderId);
            } catch (RuntimeException $exception) {
                // Provider unavailable — keep UNKNOWN, create incident
                Log::warning('Reconciliation: provider unavailable', [
                    'intent_id' => $locked->id,
                    'error' => $exception->getMessage(),
                ]);

                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                ])->save();

                $this->createIncident($locked, $attempt, 'provider_unavailable', $exception->getMessage());

                return 'unknown';
            }

            if ($providerCharge !== null) {
                // Provider confirms charge exists — persist it
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_CONFIRMED,
                    'provider_reference' => $providerCharge['reference'] ?? $providerOrderId,
                    'response_payload' => $providerCharge,
                    'completed_at' => now(),
                ])->save();

                $locked->forceFill([
                    'gateway_reference' => $providerCharge['reference'] ?? $providerOrderId,
                    'gateway_status' => 'PENDING',
                    'gateway_payload' => $providerCharge,
                ])->save();

                Log::info('Recovery: provider charge found via reconciliation', [
                    'intent_id' => $locked->id,
                    'attempt' => $attempt,
                    'provider_reference' => $providerCharge['reference'] ?? $providerOrderId,
                ]);

                return 'recovered';
            }

            // Provider authoritatively says not found — safe to retry the
            // SAME attempt with the SAME idempotency key and provider order ID.
            // Do NOT create a new attempt.
            $attemptRecord->forceFill([
                'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
                'completed_at' => null,
            ])->save();

            $locked->forceFill(['gateway_status' => 'PENDING'])->save();

            Log::info('Recovery: provider confirms no charge, intent reset to PENDING for safe retry', [
                'intent_id' => $locked->id,
                'attempt' => $attempt,
            ]);

            return 'retried';
        });
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
