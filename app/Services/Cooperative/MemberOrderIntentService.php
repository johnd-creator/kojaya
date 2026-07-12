<?php

namespace App\Services\Cooperative;

use App\Exceptions\PaymentIntentConflictException;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MemberOrderIntentService
{
    public function __construct(
        private readonly MemberOrderReservationService $reservationService,
    ) {}

    /**
     * Resolve an existing intent for the same unique key, or create a new
     * one with stock reservation.
     *
     * @param  array<string, mixed>  $canonicalRequest
     * @param  array<int, array<string, mixed>>  $items
     */
    public function resolveOrCreate(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        array $canonicalRequest,
        array $items,
    ): IntentResolution {
        $fingerprint = $this->computeFingerprint($member->id, $payableType, $canonicalRequest);
        $amount = (float) ($canonicalRequest['amount'] ?? 0);
        $channel = (string) ($canonicalRequest['channel'] ?? 'QRIS');

        return $this->attemptCreate(
            member: $member,
            payableType: $payableType,
            clientReference: $clientReference,
            fingerprint: $fingerprint,
            amount: $amount,
            channel: $channel,
            canonicalRequest: $canonicalRequest,
            items: $items,
        );
    }

    /**
     * @param  array<string, mixed>  $canonicalRequest
     * @param  array<int, array<string, mixed>>  $items
     */
    private function attemptCreate(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        string $fingerprint,
        float $amount,
        string $channel,
        array $canonicalRequest,
        array $items,
    ): IntentResolution {
        try {
            return DB::transaction(function () use ($member, $payableType, $clientReference, $fingerprint, $amount, $channel, $canonicalRequest, $items): IntentResolution {
                $existing = MemberPaymentIntent::query()
                    ->where('cooperative_member_id', $member->id)
                    ->where('payable_type', $payableType)
                    ->where('client_reference', $clientReference)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($existing) {
                    $this->validateExisting($existing, $fingerprint, $amount, $channel);

                    return new IntentResolution($existing, created: false);
                }

                $reservedItems = $this->reservationService->reserve($items);

                $intent = MemberPaymentIntent::query()->create([
                    'user_id' => $canonicalRequest['user_id'] ?? null,
                    'cooperative_member_id' => $member->id,
                    'payable_type' => $payableType,
                    'payable_id' => $canonicalRequest['payable_id'] ?? null,
                    'client_reference' => $clientReference,
                    'request_fingerprint' => $fingerprint,
                    'amount' => $amount,
                    'channel' => $channel,
                    'gateway_status' => 'PENDING',
                    'reservation_status' => MemberPaymentIntent::RESERVATION_RESERVED,
                    'settlement_status' => 'NOT_SETTLED',
                    'metadata' => $this->buildMetadata($canonicalRequest, $reservedItems),
                    'expires_at' => $canonicalRequest['expires_at'] ?? now()->addMinutes(30),
                ]);

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
                amount: $amount,
                channel: $channel,
            );
        }
    }

    private function resolveAfterUniqueViolation(
        CooperativeMember $member,
        string $payableType,
        string $clientReference,
        string $fingerprint,
        float $amount,
        string $channel,
    ): IntentResolution {
        return DB::transaction(function () use ($member, $payableType, $clientReference, $fingerprint, $amount, $channel): IntentResolution {
            $existing = MemberPaymentIntent::query()
                ->where('cooperative_member_id', $member->id)
                ->where('payable_type', $payableType)
                ->where('client_reference', $clientReference)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateExisting($existing, $fingerprint, $amount, $channel);

            return new IntentResolution($existing, created: false);
        });
    }

    private function validateExisting(MemberPaymentIntent $intent, string $fingerprint, float $amount, string $channel): void
    {
        $gatewayStatus = strtoupper((string) $intent->gateway_status);

        if ($intent->settled_at !== null || $intent->settlementStatus()->isTerminal()) {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah mencapai status terminal (settled). Gunakan client_reference baru.'
            );
        }

        if (in_array($gatewayStatus, ['PAID', 'EXPIRED', 'CANCELLED', 'DENIED', 'FAILED'], true)) {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah kedaluwarsa atau telah mencapai status terminal. Gunakan client_reference baru.'
            );
        }

        if ($intent->expires_at?->isPast() === true) {
            throw PaymentIntentConflictException::terminalState(
                'Client reference sudah kedaluwarsa. Gunakan client_reference baru.'
            );
        }

        if (abs((float) $intent->amount - $amount) > 0.005) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk nominal pembayaran berbeda. Gunakan client_reference baru.'
            );
        }

        if ((string) $intent->channel !== $channel) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk channel pembayaran berbeda. Gunakan client_reference baru.'
            );
        }

        if ($intent->request_fingerprint !== null && $intent->request_fingerprint !== $fingerprint) {
            throw PaymentIntentConflictException::payloadMismatch(
                'Client reference sudah dipakai untuk payload berbeda. Gunakan client_reference baru.'
            );
        }
    }

    /**
     * Deterministic SHA-256 fingerprint of the canonical request.
     *
     * @param  array<string, mixed>  $canonicalRequest
     */
    private function computeFingerprint(int $memberId, string $payableType, array $canonicalRequest): string
    {
        $items = $canonicalRequest['items'] ?? [];
        $sortable = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $sortable[] = [
                'pid' => (int) ($item['pos_product_id'] ?? 0),
                'qty' => (int) ($item['quantity'] ?? 1),
                'price' => round((float) ($item['unit_price'] ?? $item['line_total'] ?? 0), 2),
            ];
        }

        usort($sortable, static fn (array $a, array $b): int => $a['pid'] <=> $b['pid']);

        $customization = $canonicalRequest['customization'] ?? null;
        $fulfillment = $canonicalRequest['fulfillment_method'] ?? $canonicalRequest['fulfillment'] ?? null;

        $payload = json_encode([
            'member' => $memberId,
            'payable_type' => $payableType,
            'items' => $sortable,
            'customization' => $customization,
            'fulfillment' => $fulfillment,
            'channel' => (string) ($canonicalRequest['channel'] ?? 'QRIS'),
            'amount' => round((float) ($canonicalRequest['amount'] ?? 0), 2),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $payload);
    }

    /**
     * @param  array<string, mixed>  $canonicalRequest
     * @param  array<int, array<string, mixed>>  $reservedItems
     * @return array<string, mixed>
     */
    private function buildMetadata(array $canonicalRequest, array $reservedItems): array
    {
        $metadata = $canonicalRequest['metadata'] ?? [];

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $metadata['items'] = $reservedItems;
        $metadata['client_reference'] = $canonicalRequest['client_reference'] ?? null;

        if (isset($canonicalRequest['fulfillment_method'])) {
            $metadata['fulfillment_method'] = $canonicalRequest['fulfillment_method'];
        }

        if (isset($canonicalRequest['pickup_location'])) {
            $metadata['pickup_location'] = $canonicalRequest['pickup_location'];
        }

        if (isset($canonicalRequest['description'])) {
            $metadata['description'] = $canonicalRequest['description'];
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
