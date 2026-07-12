<?php

namespace App\Models;

use App\Enums\PaymentGatewayStatus;
use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPaymentIntent extends Model
{
    /** @use HasFactory<\Database\Factories\MemberPaymentIntentFactory> */
    use HasFactory;

    public const PAYABLE_DUES_INVOICE = 'dues_invoice';

    public const PAYABLE_LOAN_INSTALLMENT = 'loan_installment';

    public const PAYABLE_POS_CREDIT = 'pos_credit';

    public const PAYABLE_COFFEE_ORDER = 'coffee_order';

    public const PAYABLE_STORE_ORDER = 'store_order';

    public const ORDER_PAYABLE_TYPES = [
        self::PAYABLE_COFFEE_ORDER,
        self::PAYABLE_STORE_ORDER,
    ];

    public const RESERVATION_RESERVED = 'RESERVED';

    public const RESERVATION_CONSUMED = 'CONSUMED';

    public const RESERVATION_RELEASED = 'RELEASED';

    public const RESERVATION_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'user_id',
        'cooperative_member_id',
        'payable_type',
        'payable_id',
        'client_reference',
        'request_fingerprint',
        'amount',
        'channel',
        'gateway_provider',
        'gateway_reference',
        'gateway_status',
        'gateway_payload',
        'charge_attempt',
        'metadata',
        'reservation_status',
        'settlement_status',
        'expires_at',
        'settled_at',
        'settled_by_service',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'metadata' => 'array',
            'charge_attempt' => 'integer',
            'expires_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    public function gatewayStatus(): PaymentGatewayStatus
    {
        return PaymentGatewayStatus::tryFrom((string) $this->gateway_status)
            ?? PaymentGatewayStatus::Pending;
    }

    public function reservationStatus(): PaymentReservationStatus
    {
        if ($this->reservation_status !== null) {
            return PaymentReservationStatus::tryFrom($this->reservation_status)
                ?? PaymentReservationStatus::None;
        }

        $metadata = $this->metadata ?? [];

        if (isset($metadata['reservation_consumed_at'])) {
            return PaymentReservationStatus::Consumed;
        }

        if (isset($metadata['reservation_released_at'])) {
            return PaymentReservationStatus::Released;
        }

        return is_array($metadata['items'] ?? null) && $metadata['items'] !== []
            ? PaymentReservationStatus::Reserved
            : PaymentReservationStatus::None;
    }

    public function settlementStatus(): PaymentSettlementStatus
    {
        return PaymentSettlementStatus::tryFrom((string) $this->settlement_status)
            ?? ($this->settled_at !== null
                ? PaymentSettlementStatus::Settled
                : PaymentSettlementStatus::NotSettled);
    }

    public function isOrderType(): bool
    {
        return in_array($this->payable_type, self::ORDER_PAYABLE_TYPES, true);
    }

    /**
     * Validate that the current combination of gateway, reservation, and
     * settlement states is legal according to the state machine invariants.
     */
    public function isStateCombinationValid(): bool
    {
        $gateway = $this->gatewayStatus();
        $reservation = $this->reservationStatus();
        $settlement = $this->settlementStatus();

        if ($gateway->isPaid() && in_array($reservation, [
            PaymentReservationStatus::Expired,
            PaymentReservationStatus::Released,
        ], true)) {
            return false;
        }

        if ($gateway->isPaid() && $reservation === PaymentReservationStatus::Consumed
            && $settlement !== PaymentSettlementStatus::Settled
            && $settlement !== PaymentSettlementStatus::Settling) {
            return false;
        }

        if ($settlement === PaymentSettlementStatus::Settled && ! $gateway->isPaid()) {
            return false;
        }

        return true;
    }
}
