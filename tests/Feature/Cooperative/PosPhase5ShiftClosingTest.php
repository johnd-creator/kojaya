<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosAuditLog;
use App\Models\PosCashierShift;
use App\Models\PosCategory;
use App\Models\PosDailyClosing;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\PosCashierShiftService;
use App\Services\Cooperative\PosDailyClosingService;
use App\Services\Cooperative\PosJournalPostingService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase5ShiftClosingTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_can_open_and_close_shift(): void
    {
        $cashier = $this->cashier();
        $service = app(PosCashierShiftService::class);

        $shift = $service->openShift($cashier, 100000);
        $this->assertSame(PosCashierShift::STATUS_OPEN, $shift->status);

        $stats = $service->computeShiftStats($shift);
        $this->assertSame(0, $stats['transaction_count']);
        $this->assertSame(100000.0, $stats['expected_cash']);

        $closed = $service->closeShift($shift, 100000);
        $this->assertSame(PosCashierShift::STATUS_CLOSED, $closed->status);
        $this->assertSame(0.0, (float) $closed->cash_difference);
    }

    public function test_shift_difference_is_calculated_from_cash_sales(): void
    {
        $cashier = $this->cashier();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        $service = app(PosCashierShiftService::class);
        $shift = $service->openShift($cashier, 100000);

        app(PosTransactionService::class)->create([
            'pos_cashier_shift_id' => $shift->id,
            'client_reference' => 'PHASE5-CASH',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        $trx = \App\Models\PosTransaction::query()->where('client_reference', 'PHASE5-CASH')->first();
        $this->assertNotNull($trx, 'Transaction not found. Existing trx count: '.\App\Models\PosTransaction::query()->count());
        $this->assertSame($shift->id, $trx->pos_cashier_shift_id);

        $closed = $service->closeShift($shift, 104000);
        $shift->refresh();
        $this->assertSame(1, (int) $shift->transaction_count);
        $this->assertSame(5000.0, (float) $shift->total_cash_sales);
        $this->assertSame(105000.0, (float) $shift->expected_cash);
        $this->assertSame(-1000.0, (float) $shift->cash_difference);
    }

    public function test_cannot_open_second_shift_without_closing(): void
    {
        $cashier = $this->cashier();
        $service = app(PosCashierShiftService::class);
        $service->openShift($cashier, 50000);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->openShift($cashier, 50000);
    }

    public function test_daily_closing_locks_day_and_posts_journal(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $supervisorMember = CooperativeMember::factory()->create([
            'user_id' => $supervisor->id,
            'status' => 'ACTIVE',
        ]);
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $trx = app(PosTransactionService::class)->create([
            'cooperative_member_id' => $supervisorMember->id,
            'client_reference' => 'PHASE5-CLOSE',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        $service = app(PosDailyClosingService::class);
        $today = now()->toDateString();
        $closing = $service->closeDay($today, $supervisor);

        $this->assertSame($today, $closing->closing_date->toDateString());
        $this->assertTrue($closing->is_locked);
        $this->assertSame(1, (int) $closing->transaction_count);
        $this->assertSame(5000.0, (float) $closing->gross_sales);
        $this->assertNotEmpty($closing->payment_summary);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertSame(5000.0, (float) $journal->credit);
    }

    public function test_locked_day_cannot_be_closed_again(): void
    {
        $supervisor = $this->supervisor();
        $service = app(PosDailyClosingService::class);
        $today = now()->toDateString();
        $service->closeDay($today, $supervisor);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->closeDay($today, $supervisor);
    }

    public function test_journal_posting_creates_sale_and_cogs_entries(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create(['credit_limit' => 50000, 'status' => 'ACTIVE']);
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $service = app(PosTransactionService::class);
        $journalService = app(PosJournalPostingService::class);

        $trx = $service->create([
            'cooperative_member_id' => $member->id,
            'client_reference' => 'PHASE5-JOURNAL',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $journalService->postSale($trx);
        $journalService->postCogs($trx);

        $saleJournal = CooperativeLedgerEntry::query()->where('entry_type', 'POS_SALE')->latest('id')->first();
        $cogsJournal = CooperativeLedgerEntry::query()->where('entry_type', 'POS_COGS')->latest('id')->first();

        $this->assertNotNull($saleJournal);
        $this->assertNotNull($cogsJournal);
        $this->assertSame(10000.0, (float) $saleJournal->credit);
    }

    public function test_journal_member_credit_entry(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create(['credit_limit' => 50000, 'status' => 'ACTIVE']);
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $trx = app(PosTransactionService::class)->create([
            'cooperative_member_id' => $member->id,
            'client_reference' => 'PHASE5-CREDIT-JOURNAL',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'MEMBER_CREDIT', 'amount' => 5000]],
        ], $cashier);

        app(PosJournalPostingService::class)->postMemberCredit($trx);

        $entry = CooperativeLedgerEntry::query()->where('entry_type', 'POS_MEMBER_CREDIT')->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(5000.0, (float) $entry->debit);
    }

    public function test_journal_shift_difference_entry(): void
    {
        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        app(PosJournalPostingService::class)->postShiftDifference(99, -500.0, $member->id);
        $entry = CooperativeLedgerEntry::query()->where('entry_type', 'POS_SHIFT_DIFF')->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(500.0, (float) $entry->debit);
    }

    public function test_audit_log_is_written_on_shift_opened_and_closed(): void
    {
        $cashier = $this->cashier();
        $service = app(PosCashierShiftService::class);
        $shift = $service->openShift($cashier, 100000);
        $service->closeShift($shift, 100000);

        $events = PosAuditLog::query()
            ->where('entity_type', PosCashierShift::class)
            ->where('entity_id', $shift->id)
            ->pluck('event')
            ->all();
        $this->assertContains('shift.opened', $events);
        $this->assertContains('shift.closed', $events);
    }

    public function test_shifts_page_and_closings_page_are_accessible(): void
    {
        $user = $this->cashier();
        $user->givePermissionTo('view_pos_reports');

        $this->actingAs($user)->get(route('cooperative.pos.shifts.index'))->assertOk();
        $this->actingAs($user)->get(route('cooperative.pos.closings.index'))->assertOk();
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('access_cooperative_pos');

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'manage_pos_products', 'view_pos_reports']);

        return $user;
    }
}
