<?php

namespace Tests\Unit;

use App\Enums\PaymentGatewayStatus;
use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use PHPUnit\Framework\TestCase;

class PaymentStateMachineTest extends TestCase
{
    public function test_gateway_status_valid_transitions_from_pending(): void
    {
        $pending = PaymentGatewayStatus::Pending;

        $this->assertTrue($pending->canTransitionTo(PaymentGatewayStatus::Paid));
        $this->assertTrue($pending->canTransitionTo(PaymentGatewayStatus::Expired));
        $this->assertTrue($pending->canTransitionTo(PaymentGatewayStatus::Failed));
        $this->assertTrue($pending->canTransitionTo(PaymentGatewayStatus::Cancelled));
        $this->assertTrue($pending->canTransitionTo(PaymentGatewayStatus::Denied));
    }

    public function test_gateway_status_paid_is_terminal(): void
    {
        $this->assertTrue(PaymentGatewayStatus::Paid->isTerminal());
        $this->assertFalse(PaymentGatewayStatus::Paid->canTransitionTo(PaymentGatewayStatus::Expired));
        $this->assertFalse(PaymentGatewayStatus::Paid->canTransitionTo(PaymentGatewayStatus::Cancelled));
    }

    public function test_gateway_status_failed_can_recover_to_paid(): void
    {
        $this->assertTrue(
            PaymentGatewayStatus::Failed->canTransitionTo(PaymentGatewayStatus::Paid)
        );
    }

    public function test_gateway_status_new_to_charge_creating(): void
    {
        $this->assertTrue(
            PaymentGatewayStatus::New->canTransitionTo(PaymentGatewayStatus::ChargeCreating)
        );
        $this->assertTrue(
            PaymentGatewayStatus::New->canTransitionTo(PaymentGatewayStatus::Pending)
        );
    }

    public function test_gateway_status_charge_creating_to_pending(): void
    {
        $this->assertTrue(
            PaymentGatewayStatus::ChargeCreating->canTransitionTo(PaymentGatewayStatus::Pending)
        );
        $this->assertTrue(
            PaymentGatewayStatus::ChargeCreating->canTransitionTo(PaymentGatewayStatus::Failed)
        );
    }

    public function test_same_status_is_allowed(): void
    {
        $this->assertTrue(PaymentGatewayStatus::Pending->canTransitionTo(PaymentGatewayStatus::Pending));
        $this->assertTrue(PaymentGatewayStatus::Paid->canTransitionTo(PaymentGatewayStatus::Paid));
    }

    public function test_paid_cannot_go_to_expired_or_cancelled(): void
    {
        $paid = PaymentGatewayStatus::Paid;

        foreach ([PaymentGatewayStatus::Expired, PaymentGatewayStatus::Cancelled] as $target) {
            $this->assertFalse($paid->canTransitionTo($target));
        }
    }

    public function test_reservation_terminal_states(): void
    {
        $this->assertTrue(PaymentReservationStatus::Consumed->isTerminal());
        $this->assertTrue(PaymentReservationStatus::Released->isTerminal());
        $this->assertTrue(PaymentReservationStatus::Expired->isTerminal());
    }

    public function test_reservation_reserved_is_active(): void
    {
        $this->assertTrue(PaymentReservationStatus::Reserved->isActive());
        $this->assertFalse(PaymentReservationStatus::Consumed->isActive());
        $this->assertFalse(PaymentReservationStatus::None->isActive());
    }

    public function test_settlement_terminal_states(): void
    {
        $this->assertTrue(PaymentSettlementStatus::Settled->isTerminal());
        $this->assertTrue(PaymentSettlementStatus::Failed->isTerminal());
        $this->assertFalse(PaymentSettlementStatus::NotSettled->isTerminal());
        $this->assertFalse(PaymentSettlementStatus::Settling->isTerminal());
    }
}
