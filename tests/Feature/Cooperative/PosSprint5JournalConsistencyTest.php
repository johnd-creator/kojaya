<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Cooperative\PosJournalPostingService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosSprint5JournalConsistencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cash_sale_to_non_member_posts_sale_and_cogs(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-NONMEMBER',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_SALE',
            'credit' => 10000,
            'cooperative_member_id' => null,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_COGS',
            'debit' => 2000,
            'cooperative_member_id' => null,
        ]);
    }

    public function test_member_credit_sale_posts_three_entries(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1500,
            'sale_price' => 6000,
            'stock' => 5,
        ]);

        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'MEMBER_CREDIT', 'amount' => 12000]],
        ], $cashier);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_SALE',
            'credit' => 12000,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_COGS',
            'debit' => 3000,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_MEMBER_CREDIT',
            'debit' => 12000,
            'cooperative_member_id' => $member->id,
        ]);
    }

    public function test_return_posts_credit_to_member_ledger_and_contra_revenue(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-RETURN',
            'cooperative_member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $return = app(PosReturnService::class)->create([
            'pos_transaction_id' => $tx->id,
            'reason' => 'Pelanggan berubah pikiran',
            'items' => [['pos_transaction_item_id' => $tx->items->first()->id, 'quantity' => 1]],
        ], $cashier);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosReturn::class,
            'source_id' => $return->id,
            'entry_type' => 'POS_RETURN',
            'credit' => (float) $return->total_amount,
            'cooperative_member_id' => $member->id,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosReturn::class,
            'source_id' => $return->id,
            'entry_type' => 'POS_RETURN_REVERSAL',
            'debit' => (float) $return->total_amount,
            'cooperative_member_id' => null,
        ]);
    }

    public function test_void_does_not_duplicate_sale_or_cogs_posting(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-VOID',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $tx->id), [
            'reason' => 'Test void',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->firstOrFail();
        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);

        $saleCount = CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->where('entry_type', 'POS_SALE')
            ->count();
        $cogsCount = CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->where('entry_type', 'POS_COGS')
            ->count();

        $this->assertSame(1, $saleCount);
        $this->assertSame(1, $cogsCount);
    }

    public function test_void_post_three_reversing_entries(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $member = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-VOID-REVERSAL',
            'cooperative_member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'MEMBER_CREDIT', 'amount' => 10000]],
        ], $cashier);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $tx->id), [
            'reason' => 'Test void reversal',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->firstOrFail();
        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_SALE_REVERSAL',
            'debit' => 10000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_COGS_REVERSAL',
            'debit' => 0,
            'credit' => 2000,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosTransaction::class,
            'source_id' => $tx->id,
            'entry_type' => 'POS_MEMBER_CREDIT_REVERSAL',
            'debit' => 0,
            'credit' => 10000,
            'cooperative_member_id' => $member->id,
        ]);
    }

    public function test_void_reversal_is_idempotent(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-VOID-IDEMPOTENT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $tx->id), [
            'reason' => 'Test void idempotency',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->firstOrFail();
        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);

        $reversalCount = CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->where('entry_type', 'POS_SALE_REVERSAL')
            ->count();
        $this->assertSame(1, $reversalCount);
    }

    public function test_repeated_posting_for_same_source_is_idempotent(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-IDEMPOTENT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $service = app(PosJournalPostingService::class);
        $service->postSale($tx);
        $service->postCogs($tx);
        $service->postMemberCredit($tx);

        $this->assertSame(2, CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->count());
    }

    public function test_cogs_uses_snapshot_cost_not_current(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-COGS-SNAPSHOT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000]],
        ], $cashier);

        $product->update(['cost_price' => 2000]);
        $product->save();

        $cogs = CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->where('entry_type', 'POS_COGS')
            ->firstOrFail();

        $this->assertSame(3000.0, (float) $cogs->debit);
    }

    public function test_posting_service_returns_null_for_zero_amount_sale(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 0,
            'stock' => 5,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S5-ZERO',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 0]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 0, 'cash_received' => 0]],
        ], $cashier);

        $this->assertSame(0, CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->count());
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports', 'manage_pos_products']);

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        return $user;
    }
}
