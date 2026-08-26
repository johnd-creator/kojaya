<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Cooperative\MemberStoreCheckoutService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreCreditVoidRefundTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_account_paid_transaction_void_refunds_balance_and_preserves_snapshot(): void
    {
        [$cashier, $supervisor, $member, $product] = $this->voidFixture();
        $account = $this->accountFor($member, openingBalance: 500000);

        // 1 + 2: pay with MEMBER_STORE_ACCOUNT and confirm the balance decreases.
        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-VOID-001',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Budi Pembeli',
            'purchase_note' => 'Untuk konsumsi kantor',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ])->assertSuccessful();

        $storePaid = (int) PosTransaction::query()
            ->where('client_reference', 'SC-VOID-001')
            ->firstOrFail()
            ->payments
            ->where('payment_method', 'MEMBER_STORE_ACCOUNT')
            ->sum('amount');

        $this->assertSame(500000 - $storePaid, $account->refresh()->signedBalance(), 'Balance must decrease by the store-account payment.');

        $transaction = PosTransaction::query()->where('client_reference', 'SC-VOID-001')->firstOrFail();

        // 3: cashier requests the void, supervisor approves it.
        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Salah input quantity oleh kasir',
        ])->assertRedirect();

        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $transaction->id)->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        // 4: balance restored by exactly the store-account payment amount.
        $this->assertSame(500000, $account->refresh()->signedBalance(), 'Balance must be restored to the opening balance after void.');

        // 5: ledger refund entry exists and carries the void status.
        $refund = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_refund')
            ->firstOrFail();

        $this->assertSame($storePaid, (int) $refund->amount);
        $this->assertSame('void', $refund->metadata['status'] ?? null, 'Refund ledger entry must carry the void status.');
        $this->assertSame(500000, (int) $refund->balance_after);

        // 6: immutable snapshot fields stay consistent with the original purchase.
        $purchase = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_purchase')
            ->firstOrFail();

        $this->assertSame($purchase->purchaser_name, $refund->purchaser_name);
        $this->assertSame($purchase->purchase_note, $refund->purchase_note);
        $this->assertSame($purchase->transaction_no, $refund->transaction_no);
        $this->assertSame($cashier->id, $purchase->actor_user_id);
        $this->assertNotSame('', (string) $refund->transaction_no);
    }

    public function test_void_flow_does_not_double_refund_ledger_on_replay(): void
    {
        [$cashier, $supervisor, $member, $product] = $this->voidFixture();
        $account = $this->accountFor($member, openingBalance: 500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-VOID-IDEM',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Budi Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $transaction = PosTransaction::query()->where('client_reference', 'SC-VOID-IDEM')->firstOrFail();
        $storePaid = (int) $transaction->payments->where('payment_method', 'MEMBER_STORE_ACCOUNT')->sum('amount');

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Void untuk uji idempotensi',
        ])->assertRedirect();

        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $transaction->id)->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $balanceAfterVoid = $account->refresh()->signedBalance();

        // 7a: the void request itself is one-shot — re-approving the already
        // processed request must not re-enter the refund path.
        try {
            $this->app->make(\App\Services\Cooperative\PosTransactionService::class)
                ->approveVoid($voidRequest->refresh(), $supervisor);
            $this->fail('Re-approving an already processed void request must throw.');
        } catch (ValidationException) {
            // expected — the request is no longer pending.
        }

        // 7b: the ledger posting is idempotent — invoking the void refund path
        // again resolves to the existing entry and never posts a second credit.
        $checkout = $this->app->make(MemberStoreCheckoutService::class);
        $replay = $checkout->postVoidRefund(
            account: $account->refresh(),
            transaction: $transaction->refresh(),
            amount: $storePaid,
            cashier: $supervisor,
        );

        $this->assertSame(1, MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_refund')
            ->count(), 'A replayed void refund must never create a second ledger entry.');
        $this->assertSame($balanceAfterVoid, $account->refresh()->signedBalance(), 'A replayed void refund must not change the balance.');
        $this->assertNotNull($replay);
    }

    public function test_split_tender_void_refund_never_exceeds_store_account_paid(): void
    {
        [$cashier, $supervisor, $member, $product] = $this->voidFixture();
        $account = $this->accountFor($member, openingBalance: 500000);

        // Split tender: Rp30.000 on store account + Rp70.000 cash for a Rp100.000 sale.
        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-VOID-SPLIT',
            'cooperative_member_id' => $member->id,
            'purchaser_name' => 'Budi Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [
                ['payment_method' => 'MEMBER_STORE_ACCOUNT', 'amount' => 30000],
                ['payment_method' => 'CASH', 'amount' => 70000],
            ],
        ])->assertSuccessful();

        $transaction = PosTransaction::query()->where('client_reference', 'SC-VOID-SPLIT')->with('payments')->firstOrFail();

        // Store-account balance only dropped by the Rp30.000 tendered, not the full sale.
        $this->assertSame(470000, $account->refresh()->signedBalance());

        $checkout = $this->app->make(MemberStoreCheckoutService::class);

        // 8: the refund allocator caps store-credit refunds at the original
        // store-account payment, even when asked to refund the full sale total.
        $this->assertSame(30000, $checkout->cappedStoreCreditRefund($account, $transaction, 100000));
        $this->assertSame(30000, $checkout->cappedStoreCreditRefund($account, $transaction, 30000));
        $this->assertSame(0, $checkout->cappedStoreCreditRefund($account, $transaction, 0));

        // Approving the void credits exactly the store-account portion, never more.
        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Void split tender untuk uji cap',
        ])->assertRedirect();

        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $transaction->id)->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $refund = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_refund')
            ->firstOrFail();

        $this->assertSame(30000, (int) $refund->amount, 'Split-tender void refund must equal the store-account tender, not the sale total.');
        $this->assertSame(500000, $account->refresh()->signedBalance(), 'Balance must be restored to opening after capped refund.');
    }

    private function voidFixture(): array
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);

        $supervisor = User::factory()->create(['organization_id' => $organization->id]);
        $supervisor->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$cashier, $supervisor, $member, $product];
    }

    private function accountFor(CooperativeMember $member, int $openingBalance = 0, int $creditLimit = 0)
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $opener = User::factory()->create(['organization_id' => $member->organization_id]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: $opener,
        ));
    }
}
