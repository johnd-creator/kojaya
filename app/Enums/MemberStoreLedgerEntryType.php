<?php

namespace App\Enums;

enum MemberStoreLedgerEntryType: string
{
    case OpeningBalance = 'opening_balance';
    case PosPurchase = 'pos_purchase';
    case CashFunding = 'cash_funding';
    case TransferFunding = 'transfer_funding';
    case PosRefund = 'pos_refund';
    case Reversal = 'reversal';
    case AdjustmentCredit = 'adjustment_credit';
    case AdjustmentDebit = 'adjustment_debit';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Saldo Awal',
            self::PosPurchase => 'Pembelian POS',
            self::CashFunding => 'Setoran Tunai',
            self::TransferFunding => 'Setoran Transfer',
            self::PosRefund => 'Pengembalian POS',
            self::Reversal => 'Pembatalan',
            self::AdjustmentCredit => 'Penyesuaian Kredit',
            self::AdjustmentDebit => 'Penyesuaian Debit',
        };
    }
}
