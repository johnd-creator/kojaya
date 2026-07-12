<?php

namespace App\Services\Cooperative;

use App\Exceptions\PaymentIntentConflictException;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Services\AuditLogService;
use App\Support\CanonicalOrderItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MemberOrderIntentService
{
    public function __construct(
        private readonly MemberOrderReservationService $reservationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Resolve an existing intent for the same unique key, or create a new
     * one with stock reservation.
     *
     * The raw items are canonicalised exactly once here and the same
     * canonical list flows to fingerprint, reserve, metadata, and response.
     *
     * @param  array<string, mixed>  $canonicalRequest
     * @param  array<int, array<string, mixed>>  $rawItems
     */
    public function resolveOrCreate(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        array $canonicalRequest,
        array $rawItems,
    ): IntentResolution {
        $canonicalItems = CanonicalOrderItem::canonicalise($rawItems);
        $amountMinor = array_reduce(
            $canonicalItems,
            static fn (int $carry, CanonicalOrderItem $item): int => $carry + $item->amountMinor(),
            0
        );
        $channel = (string) ($canonicalRequest['channel'] ?? 'QRIS');
        $fingerprint = $this->computeFingerprint(
            memberId: $member->id,
            payableType: $payableType,
            canonicalItems: $canonicalItems,
            canonicalRequest: $canonicalRequest,
            amountMinor: $amountMinor,
            channel: $channel,
        );

        return $this->attemptCreate(
            member: $member,
            payableType: $payableType,
            clientReference: $clientReference,
            fingerprint: $fingerprint,
            amountMinor: $amountMinor,
            channel: $channel,
            canonicalRequest: $canonicalRequest,
            canonicalItems: $canonicalItems,
        );
    }

    /**
     * @param  list<CanonicalOrderItem>  $canonicalItems
     * @param  array<string, mixed>  $canonicalRequest
     */
    private function attemptCreate(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        string $fingerprint,
        int $amountMinor,
        string $channel,
        array $canonicalRequest,
        array $canonicalItems,
    ): IntentResolution {
        try {
            return DB::transaction(function () use ($member, $payableType, $clientReference, $fingerprint, $amountMinor, $channel, $canonicalRequest, $canonicalItems): IntentResolution {
                $existing = MemberPaymentIntent::query()
                    ->where('cooperative_member_id', $member->id)
                    ->where('payable_type', $payableType)
                    ->where('client_reference', $clientReference)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($existing) {
                    $this->validateExisting($existing, $fingerprint, $amountMinor, $channel);

                    return new IntentResolution($existing, created: false);
                }

                $reservedItems = $this->reservationService->reserve($canonicalItems);

                $intent = MemberPaymentIntent::query()->create([
                    'user_id' => $canonicalRequest['user_id'] ?? null,
                    'cooperative_member_id' => $member->id,
                    'payable_type' => $payableType,
                    'payable_id' => $canonicalRequest['payable_id'] ?? null,
                    'client_reference' => $clientReference,
                    'request_fingerprint' => $fingerprint,
                    'amount' => bcdiv((string) $amountMinor, '100', 2),
                    'channel' => $channel,
                    'gateway_status' => 'PENDING',
                    'reservation_status' => MemberPaymentIntent::RESERVATION_RESERVED,
                    'settlement_status' => 'NOT_SETTLED',
                    'metadata' => $this->buildMetadata($canonicalRequest, $reservedItems),
                    'expires_at' => $canonicalRequest['expires_at'] ?? now()->addMinutes(30),
                ]);

                $this->auditLogService->log(
                    'reservation.created',
                    'member_payment_intent',
                    $intent,
                    ['reason' => 'Stock reservation created for new payment intent.']
                );

                return new IntentResolution($intent, created: true);
            });
        } catch (QueryException $exception) {
            if (! $this->isClientReferenceConflict($exception)) {
                throw $exception;
            }

            return $this->resolveAfterUniqueViolation(
                member: $member,
                payableType: $payableType,
                clientReference: $clientReference,
                fingerprint: $fingerprint,
                amountMinor: $amountMinor,
                channel: $channel,
            );
        }
    }

    private function resolveAfterUniqueViolation(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        string $fingerprint,
        int $amountMinor,
        string $channel,
    ): IntentResolution {
        return DB::transaction(function () use ($member, $payableType, $clientReference, $fingerprint, $amountMinor, $channel): IntentResolution {
            $existing = MemberPaymentIntent::query()
                ->where('cooperative_member_id', $member->id)
                ->where('payable_type', $payableType)
                ->where('client_reference', $clientReference)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateExisting($existing, $fingerprint, $amountMinor, $channel);

            return new IntentResolution($existing, created: false);
        });
    }

    /**
     * Existing intent may only be reused if it is in the exact state:
     *   gateway=PENDING, reservation=RESERVED, settlement=NOT_SETTLED,
     *   settled_at=null, expires_at>now, fingerprint exact match,
     *   amount exact match, channel exact match.
     *
     * Legacy null fingerprint fails closed.
     */
    private function validateExisting(MemberPaymentIntent $intent, string $fingerprint, int $amountMinor, string $channel): void
    {
        $gatewayStatus = strtoupper((string) $intent->gateway_status);
        $reservationStatus = $intent->reservationStatus()->value;
        $settlementStatus = $intent->settlementStatus()->value;

        // Must be exactly PENDING + RESERVED + NOT_SETTLED for reuse
        if ($gatewayStatus !== 'PENDING') {
            throw PaymentIntentConflictException::terminalState(
                'Client reference tidak dalam status PENDING. Gunakan client_reference baru.'
            );
        }

        if ($reservationStatus !== 'RESERVED') {
            throw PaymentIntentConflictException::terminalState(
                'Client reference tidak memiliki reservasi aktif. Gunakan client_reference baru.'
            );
        }

        if ($settlementStatus !== 'NOT_SETTLED') {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah mencapai status settlement terminal. Gunakan client_reference baru.'
            );
        }

        if ($intent->settled_at !== null) {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah settled. Gunakan client_reference baru.'
            );
        }

        if ($intent->expires_at?->isPast() === true) {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah kedaluwarsa. Gunakan client_reference baru.'
            );
        }

        // Amount comparison using integer minor units (no float tolerance)
        $existingAmountMinor = (int) bcmul((string) $intent->amount, '100', 0);
        if ($existingAmountMinor !== $amountMinor) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk nominal pembayaran berbeda. Gunakan client_reference baru.'
            );
        }

        if ((string) $intent->channel !== $channel) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk channel pembayaran berbeda. Gunakan client_reference baru.'
            );
        }

        // Legacy null fingerprint: fail closed
        if ($intent->request_fingerprint === null) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference tidak memiliki fingerprint. Gunakan client_reference baru.'
            );
        }

        if ($intent->request_fingerprint !== $fingerprint) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk payload berbeda. Gunakan client_reference baru.'
            );
        }
    }

    /**
     * Deterministic SHA-256 fingerprint using canonical items and
     * integer minor units for amounts.
     *
     * @param  list<CanonicalOrderItem>  $canonicalItems
     * @param  array<string, mixed>  $canonicalRequest
     */
    private function computeFingerprint(
        int $memberId,
        string $payableType,
        array $canonicalItems,
        array $canonicalRequest,
        int $amountMinor,
        string $channel,
    ): string {
        $itemPayload = [];
        foreach ($canonicalItems as $item) {
            $entry = [
                'pid' => $item->posProductId,
                'qty' => $item->quantity,
                'unit_price_minor' => (int) bcmul($item->unitPrice, '100', 0),
            ];

            if ($item->customization !== null) {
                $entry['customization'] = $item->customization;
            }

            $itemPayload[] = $entry;
        }

        $fulfillment = $canonicalRequest['fulfillment_method'] ?? null;
        $pickupLocation = $canonicalRequest['pickup_location'] ?? null;
        $notes = $canonicalRequest['notes'] ?? null;

        $payload = json_encode([
            'member' => $memberId,
            'payable_type' => $payableType,
            'items' => $itemPayload,
            'fulfillment' => $fulfillment,
            'pickup_location' => $pickupLocation,
            'notes' => $notes,
            'channel' => $channel,
            'amount_minor' => $amountMinor,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $payload);
    }

    /**
     * @param  array<string, mixed>  $canonicalRequest
     * @param  list<CanonicalOrderItem>  $reservedItems
     * @return array<string, mixed>
     */
    private function buildMetadata(array $canonicalRequest, array $reservedItems): array
    {
        $metadata = [
            'items' => array_map(fn (CanonicalOrderItem $item): array => $item->toArray(), $reservedItems),
            'client_reference' => $canonicalRequest['client_reference'] ?? null,
        ];

        if (isset($canonicalRequest['description'])) {
            $metadata['description'] = $canonicalRequest['description'];
        }

        if (isset($canonicalRequest['fulfillment_method'])) {
            $metadata['fulfillment_method'] = $canonicalRequest['fulfillment_method'];
        }

        if (isset($canonicalRequest['pickup_location'])) {
            $metadata['pickup_location'] = $canonicalRequest['pickup_location'];
        }

        if (isset($canonicalRequest['notes'])) {
            $metadata['notes'] = $canonicalRequest['notes'];
        }

        return $metadata;
    }

    private function isClientReferenceConflict(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains($message, 'member_payment_intents')
            && str_contains($message, 'client_reference');
    }
}
