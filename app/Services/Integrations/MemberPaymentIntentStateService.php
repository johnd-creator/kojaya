<?php

namespace App\Services\Integrations;

use App\Enums\PaymentGatewayStatus;
use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use App\Models\MemberPaymentIntent;
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
     */
    public function applyGatewayEvent(
        string $gatewayReference,
        string $newStatus,
        ?array $rawPayload = null,
    ): ?MemberPaymentIntent {
        $newStatus = strtoupper($newStatus);

        return DB::transaction(function () use ($gatewayReference, $newStatus, $rawPayload): ?MemberPaymentIntent {
            $intent = MemberPaymentIntent::query()
                ->where('gateway_reference', $gatewayReference)
                ->lockForUpdate()
                ->first();

            if ($intent === null) {
                return null;
            }

            $currentStatus = PaymentGatewayStatus::tryFrom(strtoupper((string) $intent->gateway_status))
                ?? PaymentGatewayStatus::Pending;
            $targetStatus = PaymentGatewayStatus::tryFrom($newStatus);

            if ($targetStatus === null) {
                Log::warning('Unknown gateway status in applyGatewayEvent', [
                    'intent_id' => $intent->id,
                    'status' => $newStatus,
                ]);

                return $intent;
            }

            if (! $currentStatus->canTransitionTo($targetStatus) && $currentStatus !== $targetStatus) {
                Log::warning('Gateway transition rejected', [
                    'intent_id' => $intent->id,
                    'current' => $currentStatus->value,
                    'target' => $targetStatus->value,
                ]);

                return $intent;
            }

            $reservation = $intent->reservationStatus();
            $settlement = $intent->settlementStatus();

            $payload = $rawPayload;
            if ($payload === null) {
                $existing = is_array($intent->gateway_payload) ? $intent->gateway_payload : [];
                $payload = $existing;
            }

            return match ($targetStatus) {
                PaymentGatewayStatus::Paid => $this->applyPaid($intent, $payload, $reservation, $settlement),
                PaymentGatewayStatus::Failed, PaymentGatewayStatus::Cancelled, PaymentGatewayStatus::Denied => $this->applyTerminalFailure($intent, $targetStatus, $payload, $reservation),
                PaymentGatewayStatus::Expired => $this->applyExpired($intent, $payload, $currentStatus, $reservation),
                default => $intent,
            };
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyPaid(
        MemberPaymentIntent $intent,
        array $payload,
        PaymentReservationStatus $reservation,
        PaymentSettlementStatus $settlement,
    ): MemberPaymentIntent {
        if ($intent->isOrderType() && $reservation !== PaymentReservationStatus::Reserved) {
            Log::warning('PAID webhook for intent without active reservation', [
                'intent_id' => $intent->id,
                'reservation' => $reservation->value,
            ]);

            $intent->forceFill([
                'gateway_status' => PaymentGatewayStatus::Paid->value,
                'gateway_payload' => $this->mergePayload($intent->gateway_payload, $payload),
            ])->save();

            $this->audit('gateway.paid_without_reservation', $intent);

            return $intent;
        }

        if ($settlement === PaymentSettlementStatus::Settled) {
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

            $gatewayStatus = PaymentGatewayStatus::tryFrom(strtoupper((string) $locked->gateway_status))
                ?? PaymentGatewayStatus::Pending;

            if ($gatewayStatus === PaymentGatewayStatus::Paid || $locked->settled_at !== null) {
                return false;
            }

            if ($gatewayStatus === PaymentGatewayStatus::Expired
                || $gatewayStatus === PaymentGatewayStatus::Cancelled
                || $gatewayStatus === PaymentGatewayStatus::Denied) {
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
     * Mark a settled intent. Called by the settlement service after successful
     * business-transaction creation.
     */
    public function markSettled(MemberPaymentIntent $intent, string $settledByService): MemberPaymentIntent
    {
        return DB::transaction(function () use ($intent, $settledByService): MemberPaymentIntent {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intent->id);

            $locked->forceFill([
                'settled_at' => now(),
                'settled_by_service' => $settledByService,
                'settlement_status' => PaymentSettlementStatus::Settled->value,
            ])->save();

            return $locked;
        });
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
