<?php

namespace Tests\Feature\Security;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosDailyClosing;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\PosClosingGuard;
use App\Services\Cooperative\PosDailyClosingService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosDailyClosingOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private PosDailyClosingService $service;

    private PosClosingGuard $guard;

    private OrganizationScopeService $scopeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->service = app(PosDailyClosingService::class);
        $this->guard = app(PosClosingGuard::class);
        $this->scopeService = app(OrganizationScopeService::class);
    }

    // =========================================================================
    // GROUP 1: SUMMARY REPORT ISOLATION (Tests 1 - 10)
    // =========================================================================

    public function test_1_org_a_transaction_count_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today);
        $this->createCompletedSale($orgA, 15000, $today);
        $this->createCompletedSale($orgB, 20000, $today);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);
        $summaryB = $this->service->summaryForDate($today, $orgB->id);

        $this->assertSame(2, $summaryA['transaction_count']);
        $this->assertSame(1, $summaryB['transaction_count']);
    }

    public function test_2_org_a_gross_sales_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today);
        $this->createCompletedSale($orgA, 15000, $today);
        $this->createCompletedSale($orgB, 50000, $today);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);

        $this->assertSame(25000.0, (float) $summaryA['gross_sales']);
    }

    public function test_3_org_a_total_discount_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today, discount: 1000);
        $this->createCompletedSale($orgB, 20000, $today, discount: 5000);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);

        $this->assertSame(1000.0, (float) $summaryA['total_discount']);
    }

    public function test_4_org_a_total_void_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createVoidedSale($orgA, 12000, $today);
        $this->createVoidedSale($orgB, 30000, $today);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);

        $this->assertSame(12000.0, (float) $summaryA['total_void']);
    }

    public function test_5_org_a_total_return_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $txA = $this->createCompletedSale($orgA, 20000, $today, quantity: 4, unitPrice: 5000);
        $txB = $this->createCompletedSale($orgB, 40000, $today, quantity: 4, unitPrice: 10000);

        $this->createReturnForTransaction($txA, quantity: 1, returnedAt: $today);
        $this->createReturnForTransaction($txB, quantity: 1, returnedAt: $today);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);

        $this->assertSame(5000.0, (float) $summaryA['total_return']);
    }

    public function test_6_org_a_net_sales_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $txA = $this->createCompletedSale($orgA, 25000, $today, quantity: 5, unitPrice: 5000);
        $this->createReturnForTransaction($txA, quantity: 1, returnedAt: $today);

        $txB = $this->createCompletedSale($orgB, 50000, $today, quantity: 5, unitPrice: 10000);
        $this->createReturnForTransaction($txB, quantity: 1, returnedAt: $today);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);

        $this->assertSame(20000.0, (float) $summaryA['net_sales']);
    }

    public function test_7_org_a_payment_summary_excludes_org_b_payments(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today, paymentMethod: 'CASH');
        $this->createCompletedSale($orgA, 15000, $today, paymentMethod: 'QRIS');
        $this->createCompletedSale($orgB, 50000, $today, paymentMethod: 'CASH');

        $paymentSummaryA = $this->service->paymentSummaryForDate($today, $orgA->id);

        $cashSummaryA = collect($paymentSummaryA)->firstWhere('method', 'CASH');
        $qrisSummaryA = collect($paymentSummaryA)->firstWhere('method', 'QRIS');

        $this->assertNotNull($cashSummaryA);
        $this->assertSame(1, $cashSummaryA['count']);
        $this->assertSame(10000.0, (float) $cashSummaryA['total']);

        $this->assertNotNull($qrisSummaryA);
        $this->assertSame(1, $qrisSummaryA['count']);
        $this->assertSame(15000.0, (float) $qrisSummaryA['total']);
    }

    public function test_8_org_a_member_credit_outstanding_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $this->createMember($orgA, outstandingBalance: 35000);
        $this->createMember($orgA, outstandingBalance: 15000);
        $this->createMember($orgB, outstandingBalance: 100000);

        $outstandingA = $this->service->memberCreditOutstanding($orgA->id);

        $this->assertSame(50000.0, (float) $outstandingA);
    }

    public function test_9_org_b_receives_its_own_independent_values(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today);
        $txB = $this->createCompletedSale($orgB, 50000, $today, quantity: 5, unitPrice: 10000);
        $this->createReturnForTransaction($txB, quantity: 1, returnedAt: $today);
        $this->createMember($orgB, outstandingBalance: 80000);

        $summaryB = $this->service->summaryForDate($today, $orgB->id);
        $paymentSummaryB = $this->service->paymentSummaryForDate($today, $orgB->id);
        $outstandingB = $this->service->memberCreditOutstanding($orgB->id);

        $this->assertSame(1, $summaryB['transaction_count']);
        $this->assertSame(50000.0, (float) $summaryB['gross_sales']);
        $this->assertSame(10000.0, (float) $summaryB['total_return']);
        $this->assertSame(40000.0, (float) $summaryB['net_sales']);
        $this->assertSame(80000.0, (float) $outstandingB);
        $this->assertSame(50000.0, (float) collect($paymentSummaryB)->firstWhere('method', 'CASH')['total']);
    }

    public function test_10_null_org_legacy_data_does_not_leak_into_unit_summary(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 20000, $today);

        // Inject unowned/null-org transaction
        DB::table('pos_transactions')->insert([
            'organization_id' => null,
            'transaction_no' => 'LEGACY-NULL-ORG-01',
            'client_reference' => 'REF-LEGACY-01',
            'subtotal' => 99000,
            'discount_amount' => 0,
            'total_amount' => 99000,
            'gross_profit' => 10000,
            'cash_received' => 99000,
            'cash_change' => 0,
            'status' => 'COMPLETED',
            'sold_at' => $today,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Member in foreign Org B
        $this->createMember($orgB, outstandingBalance: 999999);

        $summaryA = $this->service->summaryForDate($today, $orgA->id);
        $outstandingA = $this->service->memberCreditOutstanding($orgA->id);

        $this->assertSame(1, $summaryA['transaction_count']);
        $this->assertSame(20000.0, (float) $summaryA['gross_sales']);
        $this->assertSame(0.0, (float) $outstandingA);
    }

    // =========================================================================
    // GROUP 2: CLOSING OWNERSHIP & COMPOSITE UNIQUENESS (Tests 11 - 20)
    // =========================================================================

    public function test_11_org_a_closing_row_is_stamped_organization_id_a(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $closingA = $this->service->closeDay($today, $userA);

        $this->assertSame($orgA->id, $closingA->organization_id);
        $this->assertTrue($closingA->is_locked);
    }

    public function test_12_org_b_can_close_same_date_independently(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $userB = $this->createClosingUser($orgB);
        $today = now()->toDateString();

        $closingA = $this->service->closeDay($today, $userA);
        $closingB = $this->service->closeDay($today, $userB);

        $this->assertSame($orgA->id, $closingA->organization_id);
        $this->assertSame($orgB->id, $closingB->organization_id);
        $this->assertTrue($closingA->is_locked);
        $this->assertTrue($closingB->is_locked);
    }

    public function test_13_one_date_may_have_a_and_b_closing_rows_simultaneously(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $userB = $this->createClosingUser($orgB);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);
        $this->service->closeDay($today, $userB);

        $count = PosDailyClosing::query()->whereDate('closing_date', $today)->count();
        $this->assertSame(2, $count);
    }

    public function test_14_reclosing_a_same_date_is_rejected(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);

        $this->expectException(ValidationException::class);
        $this->service->closeDay($today, $userA);
    }

    public function test_15_closing_a_does_not_create_b_closing(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);

        $closingB = PosDailyClosing::query()
            ->where('organization_id', $orgB->id)
            ->whereDate('closing_date', $today)
            ->first();

        $this->assertNull($closingB);
        $this->assertFalse($this->service->isLocked($today, $orgB->id));
    }

    public function test_16_legacy_null_org_closing_does_not_count_as_a_closing(): void
    {
        [$orgA] = $this->createOrganizations();
        $today = now()->toDateString();

        // Historical global closing with NULL organization_id
        DB::table('pos_daily_closings')->insert([
            'organization_id' => null,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->service->isLocked($today, $orgA->id));

        $userA = $this->createClosingUser($orgA);
        $closingA = $this->service->closeDay($today, $userA);

        $this->assertSame($orgA->id, $closingA->organization_id);
        $this->assertTrue($closingA->is_locked);
    }

    public function test_17_legacy_null_org_closing_does_not_count_as_b_closing(): void
    {
        [, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        DB::table('pos_daily_closings')->insert([
            'organization_id' => null,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->service->isLocked($today, $orgB->id));

        $userB = $this->createClosingUser($orgB);
        $closingB = $this->service->closeDay($today, $userB);

        $this->assertSame($orgB->id, $closingB->organization_id);
        $this->assertTrue($closingB->is_locked);
    }

    public function test_18_unit_a_cannot_choose_b_via_client_input(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $this->actingAs($userA)
            ->post(route('cooperative.pos.closings.close'), [
                'date' => $today,
                'organization_id' => $orgB->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('pos_daily_closings', [
            'organization_id' => $orgB->id,
            'closing_date' => $today,
        ]);
    }

    public function test_19_null_org_non_global_actor_cannot_close(): void
    {
        $userNullOrg = User::factory()->create(['organization_id' => null]);
        $userNullOrg->givePermissionTo('view_pos_reports');
        $today = now()->toDateString();

        $this->actingAs($userNullOrg)
            ->post(route('cooperative.pos.closings.close'), [
                'date' => $today,
            ])
            ->assertForbidden();

        $this->expectException(AuthorizationException::class);
        $this->service->closeDay($today, $userNullOrg);
    }

    public function test_20_global_actor_may_target_b_only_through_authorized_target_resolution(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalUser = $this->createGlobalOperator($orgA);
        $today = now()->toDateString();

        $this->createCompletedSale($orgB, 15000, $today);
        $this->withSession(['active_organization_id' => $orgA->id]);

        $response = $this->actingAs($globalUser)
            ->post(route('cooperative.pos.closings.close'), [
                'date' => $today,
                'organization_id' => $orgB->id,
            ]);

        $response->assertRedirect();

        $closingB = PosDailyClosing::query()
            ->where('organization_id', $orgB->id)
            ->whereDate('closing_date', $today)
            ->first();

        $this->assertNotNull($closingB);
        $this->assertSame($orgB->id, $closingB->organization_id);
        $this->assertSame($globalUser->id, $closingB->closed_by);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosDailyClosing::class,
            'source_id' => $closingB->id,
            'entry_type' => 'POS_DAILY_CLOSING',
            'organization_id' => $orgB->id,
            'credit' => 15000,
        ]);
    }

    // =========================================================================
    // GROUP 3: CROSS-TENANT CLOSING LOCK (Tests 21 - 28)
    // =========================================================================

    public function test_21_close_org_a_date_rejects_new_org_a_sale(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $userA = $this->createClosingUser($orgA);
        $productA = $this->createProduct($orgA);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);

        $this->expectException(ValidationException::class);

        app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-LOCK-A-01',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashierA);
    }

    public function test_22_close_org_a_date_allows_org_b_sale_same_date(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);

        $txB = app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-OPEN-B-01',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productB->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashierB);

        $this->assertNotNull($txB->id);
        $this->assertSame($orgB->id, $txB->organization_id);
    }

    public function test_23_close_org_a_date_denies_org_a_void(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $supervisorA = $this->createSupervisor($orgA);
        $today = now()->toDateString();

        $txA = $this->createCompletedSale($orgA, 10000, $today, cashier: $cashierA);

        $userA = $this->createClosingUser($orgA);
        $this->service->closeDay($today, $userA);

        $voidReq = PosVoidRequest::query()->create([
            'pos_transaction_id' => $txA->id,
            'requested_by' => $cashierA->id,
            'reason' => 'Salah input',
            'status' => 'PENDING',
        ]);

        $this->expectException(ValidationException::class);
        app(PosTransactionService::class)->approveVoid($voidReq, $supervisorA);
    }

    public function test_24_close_org_a_date_allows_org_b_void(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $cashierB = $this->createCashier($orgB);
        $supervisorB = $this->createSupervisor($orgB);
        $today = now()->toDateString();

        $txB = $this->createCompletedSale($orgB, 10000, $today, cashier: $cashierB);

        $this->service->closeDay($today, $userA);

        $voidReq = PosVoidRequest::query()->create([
            'pos_transaction_id' => $txB->id,
            'requested_by' => $cashierB->id,
            'reason' => 'Salah input di B',
            'status' => 'PENDING',
        ]);

        $voidedTx = app(PosTransactionService::class)->approveVoid($voidReq, $supervisorB);

        $this->assertSame('VOIDED', $voidedTx->status);
    }

    public function test_25_close_org_a_date_denies_org_a_return(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $txA = $this->createCompletedSale($orgA, 20000, $today, cashier: $cashierA);

        $this->service->closeDay($today, $userA);

        $this->expectException(ValidationException::class);

        app(PosReturnService::class)->create([
            'pos_transaction_id' => $txA->id,
            'return_no' => 'RET-A-01',
            'returned_at' => $today,
            'reason' => 'Barang rusak',
            'items' => [
                [
                    'pos_transaction_item_id' => $txA->items->first()->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                ],
            ],
        ], $cashierA);
    }

    public function test_26_close_org_a_date_allows_org_b_return(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $cashierB = $this->createCashier($orgB);
        $today = now()->toDateString();

        $txB = $this->createCompletedSale($orgB, 20000, $today, cashier: $cashierB);

        $this->service->closeDay($today, $userA);

        $return = app(PosReturnService::class)->create([
            'pos_transaction_id' => $txB->id,
            'return_no' => 'RET-B-01',
            'returned_at' => $today,
            'reason' => 'Barang rusak di B',
            'items' => [
                [
                    'pos_transaction_item_id' => $txB->items->first()->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                ],
            ],
        ], $cashierB);

        $this->assertNotNull($return->id);
    }

    public function test_27_org_b_closing_later_affects_only_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userB = $this->createClosingUser($orgB);
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userB);

        $this->assertTrue($this->service->isLocked($today, $orgB->id));
        $this->assertFalse($this->service->isLocked($today, $orgA->id));

        // Org A sale succeeds
        $txA = app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-A-SUCCESS-01',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashierA);
        $this->assertNotNull($txA->id);

        // Org B sale fails
        $this->expectException(ValidationException::class);
        app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-B-FAIL-01',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productB->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashierB);
    }

    public function test_28_legacy_null_org_closing_locks_neither_a_nor_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        DB::table('pos_daily_closings')->insert([
            'organization_id' => null,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->guard->isLocked($today, $orgA->id));
        $this->assertFalse($this->guard->isLocked($today, $orgB->id));
    }

    // =========================================================================
    // GROUP 4: HISTORICAL OWNERSHIP
    // =========================================================================

    public function test_transaction_retains_immutable_org_governance_when_cashier_moves(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashier = $this->createCashier($orgA);
        $today = now()->toDateString();

        $txA = $this->createCompletedSale($orgA, 10000, $today, cashier: $cashier);
        $this->assertSame($orgA->id, $txA->organization_id);

        // Cashier moves to Org B
        $cashier->update(['organization_id' => $orgB->id]);

        // Org A is closed
        $userA = $this->createClosingUser($orgA);
        $this->service->closeDay($today, $userA);

        // Org A is locked -> transaction on Org A cannot be voided even if cashier is now in Org B
        $voidReq = PosVoidRequest::query()->create([
            'pos_transaction_id' => $txA->id,
            'requested_by' => $cashier->id,
            'reason' => 'Pindah kasir',
            'status' => 'PENDING',
        ]);

        $supervisorA = $this->createSupervisor($orgA);

        $this->expectException(ValidationException::class);
        app(PosTransactionService::class)->approveVoid($voidReq, $supervisorA);
    }

    // =========================================================================
    // GROUP 5: GLOBAL ACTOR TARGETING & ATTRIBUTION
    // =========================================================================

    public function test_global_operator_home_a_closing_target_b_stamps_b_on_closing_and_ledger(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalUser = $this->createGlobalOperator($orgA);
        $today = now()->toDateString();

        $this->createCompletedSale($orgA, 10000, $today);
        $this->createCompletedSale($orgB, 50000, $today);

        $closing = $this->service->closeDay($today, $globalUser, $orgB->id);

        $this->assertSame($orgB->id, $closing->organization_id);
        $this->assertSame(1, (int) $closing->transaction_count);
        $this->assertSame(50000.0, (float) $closing->gross_sales);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame($orgB->id, $journal->organization_id);
        $this->assertSame(50000.0, (float) $journal->credit);
    }

    // =========================================================================
    // GROUP 6: CLOSING JOURNAL ATTRIBUTION & ATOMICITY (Tests 29 - 36)
    // =========================================================================

    public function test_29_closing_journal_organization_id_equals_closing_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();
        $this->createCompletedSale($orgA, 25000, $today);

        $closing = $this->service->closeDay($today, $userA);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame($orgA->id, $journal->organization_id);
    }

    public function test_30_closing_journal_source_type_and_id_match_closing(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();
        $this->createCompletedSale($orgA, 25000, $today);

        $closing = $this->service->closeDay($today, $userA);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame(PosDailyClosing::class, $journal->source_type);
        $this->assertSame($closing->id, $journal->source_id);
        $this->assertSame('POS_DAILY_CLOSING', $journal->entry_type);
    }

    public function test_31_closing_journal_credit_equals_net_sales(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();
        $txA = $this->createCompletedSale($orgA, 30000, $today, quantity: 6, unitPrice: 5000);
        $this->createReturnForTransaction($txA, quantity: 1, returnedAt: $today);

        $closing = $this->service->closeDay($today, $userA);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame(25000.0, (float) $journal->credit);
    }

    public function test_32_global_actor_home_a_closing_b_never_creates_org_a_ledger(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalUser = $this->createGlobalOperator($orgA);
        $today = now()->toDateString();
        $this->createCompletedSale($orgB, 40000, $today);

        $closing = $this->service->closeDay($today, $globalUser, $orgB->id);

        $journalsInA = CooperativeLedgerEntry::query()
            ->where('organization_id', $orgA->id)
            ->where('source_type', PosDailyClosing::class)
            ->count();

        $this->assertSame(0, $journalsInA);
    }

    public function test_33_member_attribution_if_used_is_same_org_only(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $memberA = $this->createMember($orgA, name: 'Supervisor Member', userId: $userA->id);
        $today = now()->toDateString();
        $this->createCompletedSale($orgA, 10000, $today);

        $closing = $this->service->closeDay($today, $userA);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame($memberA->id, $journal->cooperative_member_id);
    }

    public function test_34_no_same_org_member_never_causes_fallback_to_foreign_member(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalUser = $this->createGlobalOperator($orgA);
        // User has a member profile in Org A
        $memberInA = $this->createMember($orgA, name: 'Org A Profile', userId: $globalUser->id);

        $today = now()->toDateString();
        $this->createCompletedSale($orgB, 30000, $today);

        // Global user closes Org B
        $closing = $this->service->closeDay($today, $globalUser, $orgB->id);

        $journal = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame($orgB->id, $journal->organization_id);
        // MUST NOT attach Org A member to Org B journal!
        $this->assertNull($journal->cooperative_member_id);
        $this->assertNotSame($memberInA->id, $journal->cooperative_member_id);
    }

    public function test_35_failed_closing_creates_no_surviving_journal(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        $this->service->closeDay($today, $userA);

        $countBefore = CooperativeLedgerEntry::query()->where('source_type', PosDailyClosing::class)->count();

        try {
            $this->service->closeDay($today, $userA);
        } catch (ValidationException) {
        }

        $countAfter = CooperativeLedgerEntry::query()->where('source_type', PosDailyClosing::class)->count();
        $this->assertSame($countBefore, $countAfter);
    }

    public function test_36_rejected_duplicate_close_creates_no_duplicate_journal(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();
        $this->createCompletedSale($orgA, 15000, $today);

        $this->service->closeDay($today, $userA);

        $journalCount = CooperativeLedgerEntry::query()
            ->where('source_type', PosDailyClosing::class)
            ->count();
        $this->assertSame(1, $journalCount);

        try {
            $this->service->closeDay($today, $userA);
        } catch (ValidationException) {
        }

        $this->assertSame(1, CooperativeLedgerEntry::query()->where('source_type', PosDailyClosing::class)->count());
    }

    // =========================================================================
    // GROUP 7: FUNCTIONAL PERMISSION MATRIX & HTTP ISOLATION
    // =========================================================================

    public function test_unit_actor_can_read_and_close_own_closings(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();
        $this->createCompletedSale($orgA, 10000, $today);

        $this->actingAs($userA)
            ->get(route('cooperative.pos.closings.index', ['date' => $today]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Closings/Index')
                ->where('organization_id', $orgA->id)
                ->where('summary.gross_sales', 10000)
                ->where('is_locked', false)
            );

        $this->actingAs($userA)
            ->post(route('cooperative.pos.closings.close'), ['date' => $today])
            ->assertRedirect();

        $this->assertTrue($this->service->isLocked($today, $orgA->id));
    }

    public function test_global_visibility_without_functional_permission_is_denied(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        // view_cooperative_all = yes, but view_pos_reports = no
        $user->givePermissionTo('view_cooperative_all');
        $today = now()->toDateString();

        $this->actingAs($user)
            ->get(route('cooperative.pos.closings.index', ['date' => $today, 'organization_id' => $orgB->id]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('cooperative.pos.closings.close'), ['date' => $today, 'organization_id' => $orgB->id])
            ->assertForbidden();
    }

    public function test_unit_user_passing_foreign_organization_id_is_forbidden_on_get_and_post(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createClosingUser($orgA);
        $today = now()->toDateString();

        // GET attempt
        $this->actingAs($userA)
            ->get(route('cooperative.pos.closings.index', ['date' => $today, 'organization_id' => $orgB->id]))
            ->assertForbidden();

        // POST attempt
        $this->actingAs($userA)
            ->post(route('cooperative.pos.closings.close'), ['date' => $today, 'organization_id' => $orgB->id])
            ->assertForbidden();
    }

    public function test_global_without_explicit_target_rejects_home_and_session_in_http_and_service(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $global = $this->createGlobalOperator($orgA);
        $today = now()->toDateString();

        foreach ([null, $orgA->id] as $home) {
            $global->forceFill(['organization_id' => $home])->save();
            foreach ([null, $orgB->id] as $active) {
                $this->withSession(['active_organization_id' => $active])->actingAs($global);
                $this->getJson(route('cooperative.pos.closings.index', ['date' => $today]))
                    ->assertUnprocessable()->assertJsonValidationErrors('organization_id');
                $this->postJson(route('cooperative.pos.closings.close'), ['date' => $today])
                    ->assertUnprocessable()->assertJsonValidationErrors('organization_id');

                try {
                    $this->service->closeDay($today, $global);
                    $this->fail('Direct closing must require an explicit global target.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('organization_id', $exception->errors());
                }
            }
        }

        $this->assertDatabaseCount('pos_daily_closings', 0);
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);
    }

    public function test_unit_scope_ignores_foreign_session_for_http_and_direct_closing(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $actor = $this->createClosingUser($orgA);
        $this->withSession(['active_organization_id' => $orgB->id])->actingAs($actor);

        foreach ([null, $orgA->id] as $index => $target) {
            $date = now()->subDays($index)->toDateString();
            $this->getJson(route('cooperative.pos.closings.index', ['date' => $date, 'organization_id' => $target]))
                ->assertOk();
            $this->postJson(route('cooperative.pos.closings.close'), ['date' => $date, 'organization_id' => $target])
                ->assertRedirect(route('cooperative.pos.closings.index', ['date' => $date, 'organization_id' => $orgA->id]));
            $closing = $this->service->closeDay(now()->subDays($index + 2)->toDateString(), $actor, $target);
            $this->assertSame($orgA->id, $closing->organization_id);
        }

        $this->assertDatabaseMissing('pos_daily_closings', ['organization_id' => $orgB->id]);
        $this->postJson(route('cooperative.pos.closings.close'), [
            'date' => now()->toDateString(), 'organization_id' => $orgB->id,
        ])->assertForbidden();
        $this->expectException(AuthorizationException::class);
        $this->service->closeDay(now()->toDateString(), $actor, $orgB->id);
    }

    public function test_closing_uses_authoritative_visibility_even_when_actor_home_differs(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $actor = $this->createClosingUser($orgB);
        // A controlled authority seam proves the domain never adds a home-org fallback.
        $this->mock(OrganizationScopeService::class)->shouldReceive('visibilityFor')
            ->with($actor, 'view_cooperative_all')
            ->andReturn(\App\Support\OrganizationVisibility::organization($orgA->id));
        $this->withSession(['active_organization_id' => $orgB->id]);

        $closing = $this->service->closeDay(now()->toDateString(), $actor);
        $this->assertSame($orgA->id, $closing->organization_id);
        $this->expectException(AuthorizationException::class);
        $this->service->closeDay(now()->toDateString(), $actor, $orgB->id);
    }

    public function test_invalid_explicit_global_targets_never_fall_back_in_http_or_service(): void
    {
        [$orgA] = $this->createOrganizations();
        $actor = $this->createGlobalOperator($orgA);
        $this->withSession(['active_organization_id' => $orgA->id])->actingAs($actor);
        $today = now()->toDateString();

        foreach (['', 'invalid-id', (string) \Illuminate\Support\Str::uuid()] as $target) {
            $this->getJson(route('cooperative.pos.closings.index', ['date' => $today, 'organization_id' => $target]))
                ->assertUnprocessable()->assertJsonValidationErrors('organization_id');
            $this->postJson(route('cooperative.pos.closings.close'), ['date' => $today, 'organization_id' => $target])
                ->assertUnprocessable()->assertJsonValidationErrors('organization_id');
            try {
                $this->service->closeDay($today, $actor, $target);
                $this->fail('Invalid target must not resolve to home or session.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('organization_id', $exception->errors());
            }
        }

        $this->getJson(route('cooperative.pos.closings.index', ['organization_id' => [$orgA->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('organization_id');
        $this->assertDatabaseCount('pos_daily_closings', 0);
    }

    // =========================================================================
    // GROUP 8: CONCURRENCY & SERIALIZATION
    // =========================================================================

    public function test_sequential_sale_after_closing_committed_is_rejected(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $userA = $this->createClosingUser($orgA);
        $productA = $this->createProduct($orgA);
        $today = now()->toDateString();

        // Closing runs and commits
        $this->service->closeDay($today, $userA);

        // Subsequent sale on same date/org must detect locked row inside transaction and fail
        $this->expectException(ValidationException::class);
        app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-CONCURRENCY-01',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashierA);
    }

    public function test_sequential_sale_committed_before_closing_is_included_in_snapshot(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $userA = $this->createClosingUser($orgA);
        $productA = $this->createProduct($orgA);
        $today = now()->toDateString();

        // Sale runs and commits
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'TRX-CONCURRENCY-02',
            'sold_at' => $today,
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashierA);

        $this->assertNotNull($tx->id);

        // Closing runs and computes snapshot
        $closing = $this->service->closeDay($today, $userA);

        $this->assertSame(1, (int) $closing->transaction_count);
        $this->assertSame(10000.0, (float) $closing->gross_sales);
        $this->assertSame(10000.0, (float) $closing->net_sales);
    }

    // =========================================================================
    // GROUP 9: MIGRATION ROLLBACK SAFETY
    // =========================================================================

    public function test_migration_down_throws_runtime_exception_on_cross_tenant_duplicate_dates(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $today = now()->toDateString();

        // Insert closing for Org A
        DB::table('pos_daily_closings')->insert([
            'organization_id' => $orgA->id,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert closing for Org B on same date
        DB::table('pos_daily_closings')->insert([
            'organization_id' => $orgB->id,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_06_000001_add_organization_id_to_pos_daily_closings_table.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot rollback migration: found 1 duplicate closing_date records across organizations');

        try {
            $migration->down();
        } finally {
            DB::table('pos_daily_closings')->where('organization_id', $orgB->id)->delete();
        }
    }

    public function test_migration_rollback_succeeds_when_closing_dates_are_unique(): void
    {
        [$orgA] = $this->createOrganizations();
        $today = now()->toDateString();

        DB::table('pos_daily_closings')->insert([
            'organization_id' => $orgA->id,
            'closing_date' => $today,
            'closed_at' => now(),
            'is_locked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_06_000001_add_organization_id_to_pos_daily_closings_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('pos_daily_closings', 'organization_id'));

        // Restore schema for subsequent test isolation
        $migration->up();
        $this->assertTrue(Schema::hasColumn('pos_daily_closings', 'organization_id'));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * @return array{0: Organization, 1: Organization}
     */
    private function createOrganizations(): array
    {
        return [
            Organization::factory()->create(['name' => 'Koperasi Unit A']),
            Organization::factory()->create(['name' => 'Koperasi Unit B']),
        ];
    }

    private function createCashier(Organization $org): User
    {
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('access_cooperative_pos');

        return $user;
    }

    private function createSupervisor(Organization $org): User
    {
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        return $user;
    }

    private function createClosingUser(Organization $org): User
    {
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports']);

        return $user;
    }

    private function createGlobalOperator(Organization $homeOrg): User
    {
        $user = User::factory()->create(['organization_id' => $homeOrg->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports', 'view_cooperative_all']);

        return $user;
    }

    private function createProduct(Organization $org, array $attributes = []): PosProduct
    {
        $category = PosCategory::factory()->create();

        $product = PosProduct::factory()->create([
            'organization_id' => $org->id,
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
            ...$attributes,
        ]);

        app(\App\Services\Cooperative\PosInventoryService::class)->syncDefaultLocationStocks();

        return $product;
    }

    private function createMember(Organization $org, float $outstandingBalance = 0, string $name = 'Member Test', ?int $userId = null): CooperativeMember
    {
        return CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'user_id' => $userId,
            'name' => $name,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'credit_limit' => 500000,
            'outstanding_balance' => $outstandingBalance,
        ]);
    }

    private function createCompletedSale(
        Organization $org,
        float $totalAmount,
        string $soldAt,
        float $discount = 0,
        string $paymentMethod = 'CASH',
        ?User $cashier = null,
        int $quantity = 1,
        ?float $unitPrice = null
    ): PosTransaction {
        $cashier = $cashier ?? $this->createCashier($org);
        $price = $unitPrice ?? ($totalAmount + $discount);
        $product = $this->createProduct($org, ['sale_price' => $price]);

        return app(PosTransactionService::class)->create([
            'client_reference' => 'SALE-'.uniqid(),
            'sold_at' => $soldAt,
            'discount_amount' => $discount,
            'items' => [
                [
                    'pos_product_id' => $product->id,
                    'quantity' => $quantity,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'cash_received' => $paymentMethod === 'CASH' ? $totalAmount : 0,
                ],
            ],
        ], $cashier);
    }

    private function createVoidedSale(Organization $org, float $totalAmount, string $soldAt): PosTransaction
    {
        $cashier = $this->createCashier($org);
        $supervisor = $this->createSupervisor($org);

        $tx = $this->createCompletedSale($org, $totalAmount, $soldAt, cashier: $cashier);

        $voidReq = PosVoidRequest::query()->create([
            'pos_transaction_id' => $tx->id,
            'requested_by' => $cashier->id,
            'reason' => 'Void test',
            'status' => 'PENDING',
        ]);

        return app(PosTransactionService::class)->approveVoid($voidReq, $supervisor);
    }

    private function createReturnForTransaction(
        PosTransaction $transaction,
        int $quantity = 1,
        ?string $returnedAt = null
    ): PosReturn {
        $returnedAt = $returnedAt ?? now()->toDateString();
        $cashier = $this->createCashier(Organization::find($transaction->organization_id));
        $item = $transaction->items()->first();

        return app(PosReturnService::class)->create([
            'pos_transaction_id' => $transaction->id,
            'return_no' => 'RET-'.uniqid(),
            'returned_at' => $returnedAt,
            'reason' => 'Return test',
            'items' => [
                [
                    'pos_transaction_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => (float) $item->unit_price,
                ],
            ],
        ], $cashier);
    }
}
