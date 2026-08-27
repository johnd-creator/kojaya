<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditRefundMoneyHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_fractional_store_credit_payment_is_rejected(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $this->openAccount($member, 500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-FRACTIONAL',
            'cooperative_member_id' => $member->id,
            'payments' => [
                ['payment_method' => 'MEMBER_STORE_ACCOUNT', 'amount' => 100.50],
            ],
            'purchaser_name' => 'Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ])->assertStatus(422);

        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'SC-FRACTIONAL')->count());
    }

    public function test_integer_store_credit_payment_succeeds(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->openAccount($member, 500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-INTEGER',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ])->assertSuccessful();

        $entry = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_purchase')
            ->first();

        $payment = PosTransaction::query()->where('client_reference', 'SC-INTEGER')->first()->payments->firstWhere('payment_method', 'MEMBER_STORE_ACCOUNT');

        $this->assertSame(100000, $entry->amount);
        $this->assertSame((int) $payment->amount, $entry->amount);
    }

    public function test_single_tender_full_return_refunds_full_store_amount(): void
    {
        [$cashier, $member, $product, $transaction, $account] = $this->storeCreditSale(500000, 2);

        $this->createReturn($cashier, $transaction, $product, 2);

        $refunds = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_refund')->sum('amount');
        $this->assertSame(100000, (int) $refunds);
    }

    public function test_split_tender_partial_return_capped_to_store_paid(): void
    {
        // Total 100000 = 60000 store + 40000 cash
        [$cashier, $member, $product, $transaction, $account] = $this->splitStoreCreditSale(60000, 40000, 2);
        $originalStorePaid = (int) $transaction->payments->where('payment_method', 'MEMBER_STORE_ACCOUNT')->sum('amount');
        $this->assertSame(60000, $originalStorePaid);

        // Partial return of one 50000 item. store_credit_refund = min(50000, 60000 - 0) = 50000.
        $this->createReturn($cashier, $transaction, $product, 1);

        $refunds = (int) MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_refund')->sum('amount');
        $this->assertSame(50000, $refunds, 'Store refund must never exceed the return amount nor the original store credit paid.');
    }

    public function test_multiple_partial_returns_stay_within_store_tender(): void
    {
        // Total 100000 = 50000 store + 50000 cash, product 50000 x2
        [$cashier, $member, $product, $transaction, $account] = $this->splitStoreCreditSale(50000, 50000, 2);

        // Two partial returns of 50000 each — store portion is only 50000 total.
        $this->createReturn($cashier, $transaction, $product, 1);
        $this->createReturn($cashier, $transaction, $product, 1);

        $refunds = (int) MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_refund')->sum('amount');
        $this->assertLessThanOrEqual(50000, $refunds, 'Total store refunds must not exceed original store tender.');
    }

    public function test_return_exceeding_remaining_store_tender_is_capped(): void
    {
        // 100000 = 30000 store + 70000 cash
        [$cashier, $member, $product, $transaction, $account] = $this->splitStoreCreditSale(30000, 70000, 2);

        $this->createReturn($cashier, $transaction, $product, 2);

        $refunds = (int) MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_refund')->sum('amount');
        $this->assertSame(30000, $refunds, 'Store refund must be capped at the original store tender, not the full return.');
    }

    public function test_duplicate_store_credit_purchase_does_not_create_rounding_drift(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->openAccount($member, 500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-EXACT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
        ])->assertSuccessful();

        $ledgerSum = (int) MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->sum(\Illuminate\Support\Facades\DB::raw("CASE WHEN effect = 'credit' THEN amount ELSE -amount END"));

        $this->assertSame($account->refresh()->signedBalance(), $ledgerSum, 'Cached balance must equal the signed ledger sum.');
    }

    private function checkoutFixture(): array
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);

        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000, 'sale_price' => 50000, 'stock' => 10, 'is_active' => true,
        ]);

        return [$cashier, $member, $product];
    }

    private function openAccount(CooperativeMember $member, int $openingBalance)
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $opener = User::factory()->create(['organization_id' => $member->organization_id]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            openingBalance: $openingBalance,
            openedBy: $opener,
        ));
    }

    private function storeCreditSale(int $openingBalance, int $quantity): array
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->openAccount($member, $openingBalance);

        $service = $this->app->make(PosTransactionService::class);
        $transaction = $service->create([
            'client_reference' => 'SALE-'.uniqid(),
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => $quantity]],
        ], $cashier);

        return [$cashier, $member, $product, $transaction->refresh(), $account];
    }

    private function splitStoreCreditSale(int $storeAmount, int $cashAmount, int $quantity): array
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->openAccount($member, $storeAmount + $cashAmount);

        $service = $this->app->make(PosTransactionService::class);
        $transaction = $service->create([
            'client_reference' => 'SPLIT-'.uniqid(),
            'cooperative_member_id' => $member->id,
            'payments' => [
                ['payment_method' => 'MEMBER_STORE_ACCOUNT', 'amount' => $storeAmount],
                ['payment_method' => 'CASH', 'amount' => $cashAmount],
            ],
            'purchaser_name' => 'Pembeli',
            'cash_received' => $cashAmount,
            'items' => [['pos_product_id' => $product->id, 'quantity' => $quantity]],
        ], $cashier);

        return [$cashier, $member, $product, $transaction->refresh(), $account];
    }

    private function createReturn(User $cashier, PosTransaction $transaction, PosProduct $product, int $quantity): PosReturn
    {
        $item = $transaction->items->firstWhere('pos_product_id', $product->id);

        return $this->app->make(PosReturnService::class)->create([
            'pos_transaction_id' => $transaction->id,
            'items' => [['pos_transaction_item_id' => $item->id, 'quantity' => $quantity]],
        ], $cashier);
    }
}
