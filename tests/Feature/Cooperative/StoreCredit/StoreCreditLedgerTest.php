<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Enums\MemberStoreLedgerEffect;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreCreditLedgerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_account_starts_at_zero(): void
    {
        $account = $this->openAccount();

        $this->assertSame(0, $account->signedBalance());
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
    }

    public function test_credit_funding_increases_balance(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount();

        $ledger->adjust($account, 500000, MemberStoreLedgerEffect::Credit, $actor, 'Setoran tunai');

        $this->assertSame(500000, $account->refresh()->signedBalance());
    }

    public function test_purchase_decreases_balance(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(200000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 150000, $actor, null);

        $this->assertSame(50000, $account->refresh()->signedBalance());
    }

    public function test_balance_can_become_negative_within_credit_limit(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 100000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 75000, $actor, null);

        $this->assertSame(-75000, $account->refresh()->signedBalance());
    }

    public function test_purchase_exactly_at_credit_limit_succeeds(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 100000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 100000, $actor, null);

        $this->assertSame(-100000, $account->refresh()->signedBalance());
    }

    public function test_purchase_beyond_credit_limit_fails_atomically(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 100000);

        $transaction = $this->makeTransaction();

        try {
            $ledger->postPurchase($account, $transaction, 100001, $actor, null);
            $this->fail('Expected over-limit rejection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('account', $exception->errors());
        }

        $this->assertSame(0, $account->refresh()->signedBalance());
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
    }

    public function test_payment_while_negative_reduces_debt(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 200000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 150000, $actor, null);
        $ledger->adjust($account, 100000, MemberStoreLedgerEffect::Credit, $actor, 'Bayar utang');

        $this->assertSame(-50000, $account->refresh()->signedBalance());
    }

    public function test_payment_beyond_debt_creates_positive_deposit(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 200000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 100000, $actor, null);
        $ledger->adjust($account, 300000, MemberStoreLedgerEffect::Credit, $actor, 'Bayar lebih');

        $this->assertSame(200000, $account->refresh()->signedBalance());
    }

    public function test_refund_restores_balance(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(300000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 200000, $actor, null);
        $ledger->postRefund($account, $transaction, 200000, $actor);

        $this->assertSame(300000, $account->refresh()->signedBalance());
    }

    public function test_duplicate_purchase_does_not_double_debit(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 500000);

        $transaction = $this->makeTransaction();
        $first = $ledger->postPurchase($account, $transaction, 200000, $actor, null);
        $second = $ledger->postPurchase($account, $transaction, 200000, $actor, null);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(-200000, $account->refresh()->signedBalance());
    }

    public function test_duplicate_refund_does_not_double_credit(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(300000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 200000, $actor, null);
        $first = $ledger->postRefund($account, $transaction, 200000, $actor);
        $second = $ledger->postRefund($account, $transaction, 200000, $actor);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(300000, $account->refresh()->signedBalance());
    }

    public function test_reversal_creates_new_entry_and_opposes_original(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(100000);

        $transaction = $this->makeTransaction();
        $purchase = $ledger->postPurchase($account, $transaction, 40000, $actor, null);
        $this->assertSame(60000, $account->refresh()->signedBalance());

        $reversal = $ledger->reverseEntry($purchase, $actor, 'Salah posting');

        $this->assertNotSame($purchase->id, $reversal->id);
        $this->assertSame($purchase->id, $reversal->reversal_of_entry_id);
        $this->assertSame(100000, $account->refresh()->signedBalance());
    }

    public function test_reversal_is_idempotent(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(100000);

        $transaction = $this->makeTransaction();
        $purchase = $ledger->postPurchase($account, $transaction, 40000, $actor, null);
        $first = $ledger->reverseEntry($purchase, $actor, 'Salah');
        $second = $ledger->reverseEntry($purchase, $actor, 'Salah');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(100000, $account->refresh()->signedBalance());
    }

    public function test_posted_ledger_entry_cannot_be_updated_or_deleted(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(100000);

        $transaction = $this->makeTransaction();
        $entry = $ledger->postPurchase($account, $transaction, 10000, $actor, null);

        $this->expectException(\RuntimeException::class);
        $entry->save();
    }

    public function test_posted_ledger_entry_cannot_be_deleted(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(100000);

        $transaction = $this->makeTransaction();
        $entry = $ledger->postPurchase($account, $transaction, 10000, $actor, null);

        $this->expectException(\RuntimeException::class);
        $entry->delete();
    }

    public function test_cached_balance_equals_ledger_sum(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 1000000);

        $transactionA = $this->makeTransaction('POS-A-001');
        $transactionB = $this->makeTransaction('POS-A-002');

        $ledger->postPurchase($account, $transactionA, 250000, $actor, null);
        $ledger->adjust($account, 500000, MemberStoreLedgerEffect::Credit, $actor, 'Setoran');
        $ledger->postPurchase($account, $transactionB, 100000, $actor, null);

        $sum = (int) MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->sum(\DB::raw('CASE WHEN effect = \'credit\' THEN amount ELSE -amount END'));

        $this->assertSame($sum, $account->refresh()->signedBalance());
    }

    public function test_credit_limit_cannot_be_negative(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount();

        $this->expectException(ValidationException::class);
        $ledger->changeCreditLimit($account, -1, $actor, 'test');
    }

    public function test_credit_limit_below_current_debt_requires_override(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 500000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 300000, $actor, null);

        $this->expectException(ValidationException::class);
        $ledger->changeCreditLimit($account, 100000, $actor, 'turunkan di bawah utang');
    }

    public function test_credit_limit_override_below_debt_allowed_with_permission(): void
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();
        $account = $this->openAccount(0, 500000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 300000, $actor, null);

        $ledger->changeCreditLimit($account, 100000, $actor, 'override', overrideBelowDebt: true);
        $this->assertSame(100000, $account->refresh()->credit_limit);
    }

    private function openAccount(int $openingBalance = 0, int $creditLimit = 0): MemberStoreAccount
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: $organization->id,
            cooperativeMemberId: $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: User::factory()->create(),
            reason: 'opening',
        ));
    }

    private function makeTransaction(string $no = 'POS-LEDGER-001'): PosTransaction
    {
        return PosTransaction::query()->create([
            'transaction_no' => $no.'-'.uniqid(),
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'status' => 'COMPLETED',
            'sold_at' => now()->toDateString(),
        ]);
    }
}
