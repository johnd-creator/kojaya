<?php

namespace App\Services\Integrations;

use App\Enums\PaymentGatewayStatus;
use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use App\Models\MemberPaymentIntent;
use App\Models\PaymentReconciliationIncident;
use App\Services\AuditLogService;
use App\Services\Cooperative\MemberOrderReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberPaymentIntentStateService
{
    public function __construct(
        private readonly MemberOrderReservationService $reservationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Apply a gateway event (webhook or expiry) to a member payment intent.
     *
     * This is the single authoritative lock path. No other code may write
     * gateway_status or settlement_status directly.
     *
     * @param  array<string, mixed>|null  $rawPayload
     * @param  int|null  $providerAmountMinor  Amount in integer minor units (no float conversion)
     * @param  float|null  $providerAmount  Deprecated: use providerAmountMinor instead
     */
    public function applyGatewayEvent(
        string $gatewayReference,
        string $newStatus,
        ?array $rawPayload = null,
        ?int $providerAmountMinor = null,
        ?float $providerAmount = null,
    ): ?MemberPaymentIntent {
        $newStatus = strtoupper($newStatus);

        // Backward compat: if float amount provided but no minor, convert
        // using bcmath to avoid float precision loss
        if ($providerAmountMinor === null && $providerAmount !== null) {
            $providerAmountMinor = (int) bcmul((string) $providerAmount, '100', 0);
        }

        return DB::transaction(function () use ($gatewayReference, $newStatus, $rawPayload, $providerAmountMinor): ?MemberPaymentIntent {
            $intent = MemberPaymentIntent::query()
                ->where('gateway_reference', $gatewayReference)
                ->lockForUpdate()
                ->first();

            if ($intent === null) {
                return null;
            }

            $targetStatus = PaymentGatewayStatus::tryFrom($newStatus);

            if ($targetStatus === null) {
                Log::warning('Unknown gateway status in applyGatewayEvent, ignoring (not falling back to PENDING)', [
                    'intent_id' => $intent->id,
                    'status' => $newStatus,
                ]);

                return $intent;
            }

            $currentStatus = $intent->gatewayStatus();
            $reservation = $intent->reservationStatus();
            $payload = $rawPayload ?? (is_array($intent->gateway_payload) ? $intent->gateway_payload : []);

            // PAID is a verified provider fact: always route through applyPaid
            // which handles reconciliation incidents for mismatched states.
            if ($targetStatus === PaymentGatewayStatus::Paid) {
                return $this->applyPaid($intent, $payload, $currentStatus, $reservation, $providerAmountMinor);
            }

            if (! $currentStatus->canTransitionTo($targetStatus) && $currentStatus !== $targetStatus) {
                Log::warning('Gateway transition rejected', [
                    'intent_id' => $intent->id,
                    'current' => $currentStatus->value,
                    'target' => $targetStatus->value,
                ]);

                return $intent;
            }

            return match ($targetStatus) {
                PaymentGatewayStatus::Failed, PaymentGatewayStatus::Cancelled, PaymentGatewayStatus::Denied => $this->applyTerminalFailure($intent, $targetStatus, $payload, $reservation),
                PaymentGatewayStatus::Expired => $this->applyExpired($intent, $payload, $currentStatus, $reservation),
                default => $intent,
            };
        });
    }

    /**
     * Apply PAID. Validates amount, current state, and reservation state.
     *
     * If the intent is already in a terminal gateway state, reservation is
     * not RESERVED (RELEASED/EXPIRED), or amount mismatches — create a
     * reconciliation incident. Never persist an illegal state combination.
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyPaid(
        MemberPaymentIntent $intent,
        array $payload,
        PaymentGatewayStatus $currentGateway,
        PaymentReservationStatus $reservation,
        ?int $providerAmountMinor,
    ): MemberPaymentIntent {
        $settlement = $intent->settlementStatus();

        if ($settlement === PaymentSettlementStatus::Settled) {
            return $intent;
        }

        // Amount validation in integer minor units (no float conversion)
        if ($providerAmountMinor !== null) {
            $expectedMinor = (int) bcmul((string) $intent->amount, '100', 0);

            if ($expectedMinor !== $providerAmountMinor) {
                $this->createIncident($intent, $payload, PaymentReconciliationIncident::TYPE_AMOUNT_MISMATCH, [
                    'expected_amount_minor' => (string) $expectedMinor,
                    'actual_amount_minor' => (string) $providerAmountMinor,
                    'provider_status' => 'PAID',
                    'provider_reference' => $payload['order_id'] ?? $payload['reference'] ?? null,
                ]);

                Log::error('PAID webhook amount mismatch, incident created', [
                    'intent_id' => $intent->id,
                    'expected' => $expectedMinor,
                    'actual' => $providerAmountMinor,
                ]);

                return $intent;
            }
        }

        // PAID after gateway is already terminal (CANCELLED/EXPIRED/DENIED):
        // create incident, do NOT change authoritative intent state.
        if ($currentGateway->isTerminal() && $currentGateway !== PaymentGatewayStatus::Paid) {
            $incidentType = match ($currentGateway) {
                PaymentGatewayStatus::Expired => PaymentReconciliationIncident::TYPE_PAID_AFTER_EXPIRY,
                PaymentGatewayStatus::Cancelled => PaymentReconciliationIncident::TYPE_PAID_AFTER_RELEASE,
                default => PaymentReconciliationIncident::TYPE_PAID_WITHOUT_RESERVATION,
            };

            $this->createIncident($intent, $payload, $incidentType, [
                'provider_status' => 'PAID',
                'provider_reference' => $payload['order_id'] ?? $payload['reference'] ?? null,
                'gateway_at_incident' => $currentGateway->value,
                'reservation_at_incident' => $reservation->value,
            ]);

            Log::error('PAID webhook for intent with terminal gateway state, incident created (no illegal state persisted)', [
                'intent_id' => $intent->id,
                'gateway' => $currentGateway->value,
                'reservation' => $reservation->value,
            ]);

            return $intent;
        }

        // PAID after reservation expired/released: don't persist PAID on
        // the authoritative intent — create incident instead.
        if ($intent->isOrderType() && $reservation !== PaymentReservationStatus::Reserved) {
            $incidentType = match ($reservation) {
                PaymentReservationStatus::Expired => PaymentReconciliationIncident::TYPE_PAID_AFTER_EXPIRY,
                PaymentReservationStatus::Released => PaymentReconciliationIncident::TYPE_PAID_AFTER_RELEASE,
                default => PaymentReconciliationIncident::TYPE_PAID_WITHOUT_RESERVATION,
            };

            $this->createIncident($intent, $payload, $incidentType, [
                'provider_status' => 'PAID',
                'provider_reference' => $payload['order_id'] ?? $payload['reference'] ?? null,
                'reservation_at_incident' => $reservation->value,
            ]);

            Log::error('PAID webhook for intent without active reservation, incident created (no illegal state persisted)', [
                'intent_id' => $intent->id,
                'reservation' => $reservation->value,
            ]);

            // Do NOT change gateway_status — intent stays in its current valid state
            return $intent;
        }

        $intent->forceFill([
            'gateway_status' => PaymentGatewayStatus::Paid->value,
            'gateway_payload' => $this->mergePayload($intent->gateway_payload, $payload),
            'settlement_status' => PaymentSettlementStatus::Settling->value,
        ])->save();

        $this->audit('gateway.paid', $intent);

        return $intent;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyTerminalFailure(
        MemberPaymentIntent $intent,
        PaymentGatewayStatus $target,
        array $payload,
        PaymentReservationStatus $reservation,
    ): MemberPaymentIntent {
        $intent->forceFill([
            'gateway_status' => $target->value,
            'gateway_payload' => $this->mergePayload($intent->gateway_payload, $payload),
        ])->save();

        if ($intent->isOrderType() && $reservation === PaymentReservationStatus::Reserved) {
            $this->reservationService->release($intent->refresh());
        }

        $this->audit('gateway.'.$target->value, $intent);

        return $intent;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyExpired(
        MemberPaymentIntent $intent,
        array $payload,
        PaymentGatewayStatus $currentStatus,
        PaymentReservationStatus $reservation,
    ): MemberPaymentIntent {
        if ($currentStatus === PaymentGatewayStatus::Paid) {
            Log::warning('EXPIRED webhook for already-PAID intent, ignoring', [
                'intent_id' => $intent->id,
            ]);

            return $intent;
        }

        $intent->forceFill([
            'gateway_status' => PaymentGatewayStatus::Expired->value,
            'gateway_payload' => $this->mergePayload($intent->gateway_payload, $payload),
        ])->save();

        if ($intent->isOrderType() && $reservation === PaymentReservationStatus::Reserved) {
            $this->reservationService->release($intent->refresh());
        }

        $this->audit('gateway.expired', $intent);

        return $intent;
    }

    /**
     * Expire an intent that has passed its deadline. Called by the expiry worker.
     */
    public function expireStaleIntent(MemberPaymentIntent $intent): bool
    {
        return DB::transaction(function () use ($intent): bool {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            $gatewayStatus = $locked->gatewayStatus();

            if ($gatewayStatus === PaymentGatewayStatus::Paid || $locked->settled_at !== null) {
                return false;
            }

            if (in_array($gatewayStatus, [
                PaymentGatewayStatus::Expired,
                PaymentGatewayStatus::Cancelled,
                PaymentGatewayStatus::Denied,
            ], true)) {
                return false;
            }

            $reservation = $locked->reservationStatus();

            if (! $locked->isOrderType() || $reservation !== PaymentReservationStatus::Reserved) {
                return false;
            }

            if ($locked->expires_at?->isPast() !== true) {
                return false;
            }

            $locked->forceFill([
                'gateway_status' => PaymentGatewayStatus::Expired->value,
            ])->save();

            $this->reservationService->expire($locked->refresh());

            $this->audit('gateway.expired', $locked);

            return true;
        });
    }

    /**
     * Mark a settled intent. Validates gateway PAID + reservation CONSUMED
     * for order types.
     */
    public function markSettled(MemberPaymentIntent $intent, string $settledByService): MemberPaymentIntent
    {
        return DB::transaction(function () use ($intent, $settledByService): MemberPaymentIntent {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            // Enforce: must be PAID
            if (! $locked->gatewayStatus()->isPaid()) {
                throw new \DomainException(
                    "Cannot settle intent {$locked->id}: gateway status is not PAID."
                );
            }

            // Enforce: order types must have CONSUMED reservation
            if ($locked->isOrderType() && $locked->reservationStatus() !== PaymentReservationStatus::Consumed) {
                throw new \DomainException(
                    "Cannot settle intent {$locked->id}: reservation is not CONSUMED."
                );
            }

            // Enforce: must not already be settled
            if ($locked->settled_at !== null) {
                return $locked;
            }

            $locked->forceFill([
                'settled_at' => now(),
                'settled_by_service' => $settledByService,
                'settlement_status' => PaymentSettlementStatus::Settled->value,
            ])->save();

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $extra
     */
    private function createIncident(MemberPaymentIntent $intent, array $payload, string $type, array $extra): void
    {
        $fingerprint = md5(
            $intent->id.'|'.$type.'|'.($extra['provider_reference'] ?? $intent->gateway_reference ?? '').'|'.($extra['actual_amount_minor'] ?? '')
        );

        PaymentReconciliationIncident::query()->firstOrCreate(
            ['deduplication_key' => $fingerprint],
            [
                'member_payment_intent_id' => $intent->id,
                'gateway_reference' => $intent->gateway_reference,
                'incident_type' => $type,
                'status' => PaymentReconciliationIncident::STATUS_OPEN,
                'provider_status' => $extra['provider_status'] ?? null,
                'provider_reference' => $extra['provider_reference'] ?? null,
                'expected_amount_minor' => $extra['expected_amount_minor'] ?? null,
                'actual_amount_minor' => $extra['actual_amount_minor'] ?? null,
                'webhook_payload' => $payload,
                'incident_at' => now(),
            ],
        );

        $this->auditLogService->log(
            'reconciliation.incident_created',
            'member_payment_intent',
            $intent,
            ['type' => $type, ...$extra],
        );
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergePayload(?array $existing, array $incoming): array
    {
        $payload = is_array($existing) ? $existing : [];
        $payload['latest_webhook'] = $incoming;

        return $payload;
    }

    private function audit(string $event, MemberPaymentIntent $intent): void
    {
        $this->auditLogService->log($event, 'member_payment_intent', $intent, [
            'gateway_status' => $intent->gateway_status,
            'reservation_status' => $intent->reservation_status,
            'settlement_status' => $intent->settlement_status,
        ]);
    }
}
