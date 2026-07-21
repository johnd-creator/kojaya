<?php

namespace Tests\Unit;

use App\Http\Resources\MemberStoreLedgerEntryResource;
use App\Models\MemberStoreLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemberStoreLedgerEntryResourceTest extends TestCase
{
    public function test_reference_contract_is_stable_and_typed(): void
    {
        $entry = new MemberStoreLedgerEntry([
            'entry_type' => 'pos_purchase',
            'amount' => 125000,
            'effect' => 'debit',
            'balance_before' => 500000,
            'balance_after' => 375000,
            'reference_type' => 'App\\Models\\PosTransaction',
            'reference_id' => 42,
            'purchaser_name' => 'Siti sebagai pembeli',
            'purchase_note' => 'Diambil oleh staff',
            'transaction_no' => 'POS-20260721-001',
            'metadata' => ['status' => 'purchase'],
            'occurred_at' => Carbon::parse('2026-07-21T09:30:00+07:00'),
        ]);

        $payload = (new MemberStoreLedgerEntryResource($entry))->resolve(Request::create('/'));

        $this->assertSame('pos_transaction', $payload['reference_type']);
        $this->assertSame('42', $payload['reference_id']);
        $this->assertSame('Siti sebagai pembeli', $payload['purchaser_name']);
        $this->assertSame('Diambil oleh staff', $payload['purchase_note']);
        $this->assertSame('POS-20260721-001', $payload['transaction_no']);
        $this->assertSame('purchase', $payload['status']);
        $this->assertSame('2026-07-21T09:30:00+00:00', $payload['occurred_at']);
        $this->assertMatchesRegularExpression('/T.*[+-]\\d{2}:\\d{2}$/', $payload['occurred_at']);
        $this->assertSame(375000, $payload['balance_after']);
    }

    public function test_unknown_reference_type_returns_null_and_missing_id_stays_null(): void
    {
        $entry = new MemberStoreLedgerEntry([
            'entry_type' => 'cash_funding',
            'amount' => 100000,
            'effect' => 'credit',
            'balance_before' => 0,
            'balance_after' => 100000,
            'reference_type' => 'App\\Models\\InternalSecretModel',
            'reference_id' => null,
            'occurred_at' => Carbon::parse('2026-07-21T09:30:00+07:00'),
        ]);

        $payload = (new MemberStoreLedgerEntryResource($entry))->resolve(Request::create('/'));

        $this->assertNull($payload['reference_type']);
        $this->assertNull($payload['reference_id']);
    }

    public function test_all_known_reference_types_use_stable_public_values(): void
    {
        $knownTypes = [
            'pos_transaction' => 'pos_transaction',
            'App\\Models\\PosTransaction' => 'pos_transaction',
            'pos_return' => 'pos_return',
            'App\\Models\\PosReturn' => 'pos_return',
            'funding_request' => 'funding_request',
            'App\\Models\\MemberStoreFundingRequest' => 'funding_request',
            'store_account' => 'store_account',
            'App\\Models\\MemberStoreAccount' => 'store_account',
            'ledger_entry' => 'ledger_entry',
            'App\\Models\\MemberStoreLedgerEntry' => 'ledger_entry',
        ];

        foreach ($knownTypes as $referenceType => $publicReferenceType) {
            $entry = new MemberStoreLedgerEntry([
                'entry_type' => 'cash_funding',
                'amount' => 100000,
                'effect' => 'credit',
                'balance_before' => 0,
                'balance_after' => 100000,
                'reference_type' => $referenceType,
                'reference_id' => 1,
                'occurred_at' => Carbon::parse('2026-07-21T09:30:00+07:00'),
            ]);

            $payload = (new MemberStoreLedgerEntryResource($entry))->resolve(Request::create('/'));

            $this->assertSame($publicReferenceType, $payload['reference_type'], $referenceType);
        }
    }
}
