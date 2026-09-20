<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class CooperativeFinancialFixtureSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Scenario A: SEED-04R1 metadata verification on P08 and P09.
     */
    public function test_scenario_a_seed_04r1_admin_verification_history_preserved(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $adminKop = User::query()->where('email', 'seed.admin.kop@kojaya.test')->firstOrFail();
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();

        $p08 = CooperativeMember::query()->where('member_no', 'DEV-KOP-008')->firstOrFail();
        $this->assertSame($adminKop->id, $p08->admin_validated_by);
        $this->assertNotNull($p08->admin_validated_at);
        $this->assertSame($adminKop->id, $p08->validated_by);
        $this->assertTrue($p08->validated_at->gt($p08->admin_validated_at));

        $p09 = CooperativeMember::query()->where('member_no', 'DEV-KOP-009')->firstOrFail();
        $this->assertSame($adminKop->id, $p09->admin_validated_by);
        $this->assertNotNull($p09->admin_validated_at);
        $this->assertSame($pengurus->id, $p09->validated_by);
        $this->assertTrue($p09->validated_at->gt($p09->admin_validated_at));
        $this->assertNotSame($p09->admin_validated_by, $p09->validated_by);
    }

    /**
     * Scenario B: Direct execution bootstraps entire canonical dependency chain.
     */
    public function test_scenario_b_direct_execution_bootstraps_dependencies(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        // Assert 12 canonical users exist
        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());

        // Assert 7 canonical members exist under KOP-001
        $this->assertSame(7, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());

        // Assert KOP-001 organization exists
        $this->assertTrue(Organization::query()->where('code', 'KOP-001')->exists());
    }

    /**
     * Scenario C: Non-active members (P06-P09) have strictly zero financial records.
     */
    public function test_scenario_c_non_active_members_have_zero_finance(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $nonActiveNos = ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'];
        $nonActiveIds = CooperativeMember::query()->whereIn('member_no', $nonActiveNos)->pluck('id')->all();

        $this->assertCount(4, $nonActiveIds);

        foreach ($nonActiveIds as $memberId) {
            $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, CooperativePayment::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, Loan::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, LoanPayment::query()->where('cooperative_member_id', $memberId)->count());
            $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $memberId)->count());
        }
    }

    /**
     * Scenario D: P13 is canonical EMPTY state.
     */
    public function test_scenario_d_p13_is_canonical_empty_state(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();

        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, CooperativePayment::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, LoanPayment::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $p13->id)->count());
    }

    /**
     * Scenario E: P10 paid contribution dataset (POKOK + WAJIB).
     */
    public function test_scenario_e_p10_paid_contributions_and_receipts(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();

        // Check POKOK
        $invPokok = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('cooperative_contribution_type_id', $pokok->id)
            ->where('period', '2026-01')
            ->firstOrFail();
        $this->assertSame('PAID', $invPokok->status);
        $this->assertEquals($invPokok->amount, $invPokok->paid_amount);

        $payPokok = CooperativePayment::query()->where('cooperative_dues_invoice_id', $invPokok->id)->firstOrFail();
        $this->assertSame('APPROVED', $payPokok->status);
        $this->assertSame('SEED-RC-010-001', $payPokok->receipt_no);

        $rcPokok = CooperativeReceipt::query()->where('cooperative_payment_id', $payPokok->id)->firstOrFail();
        $this->assertSame('SEED-RC-010-001', $rcPokok->receipt_no);

        // Check WAJIB
        $invWajib = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->where('period', '2026-01')
            ->firstOrFail();
        $this->assertSame('PAID', $invWajib->status);
        $this->assertEquals($invWajib->amount, $invWajib->paid_amount);

        $payWajib = CooperativePayment::query()->where('cooperative_dues_invoice_id', $invWajib->id)->firstOrFail();
        $this->assertSame('APPROVED', $payWajib->status);
        $this->assertSame('SEED-RC-010-002', $payWajib->receipt_no);

        $rcWajib = CooperativeReceipt::query()->where('cooperative_payment_id', $payWajib->id)->firstOrFail();
        $this->assertSame('SEED-RC-010-002', $rcWajib->receipt_no);
    }

    /**
     * Scenario F: P12 unpaid contribution (WAJIB 2026-06).
     */
    public function test_scenario_f_p12_unpaid_contribution(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();

        $inv = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p12->id)
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->where('period', '2026-06')
            ->firstOrFail();

        $this->assertSame('UNPAID', $inv->status);
        $this->assertEquals(0, (float) $inv->paid_amount);
        $this->assertSame('2026-06-10', $inv->due_date?->toDateString());

        // Assert zero payments and zero receipts exist for this invoice
        $this->assertSame(0, CooperativePayment::query()->where('cooperative_dues_invoice_id', $inv->id)->count());
    }

    /**
     * Scenario G: Savings ledger reconciliation.
     */
    public function test_scenario_g_savings_ledger_reconciliation(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();

        $totalApprovedPayments = CooperativePayment::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('status', 'APPROVED')
            ->sum('amount');

        $totalSavingsLedgerCredits = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('ledger_scope', 'SAVINGS')
            ->sum('credit');

        $this->assertGreaterThan(0, $totalApprovedPayments);
        $this->assertEquals((float) $totalApprovedPayments, (float) $totalSavingsLedgerCredits);
    }

    /**
     * Scenario H: P10 normal store account (+150k, limit 500k).
     */
    public function test_scenario_h_p10_normal_store_account(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();

        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p10->id)->firstOrFail();
        $this->assertSame(MemberStoreAccountStatus::Active, $account->status);
        $this->assertSame(500000, $account->credit_limit);
        $this->assertSame(150000, $account->balance);
        $this->assertSame(650000, $account->availableCredit());

        // Reconcile with ledger
        $ledgerSum = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->selectRaw("SUM(CASE WHEN effect = 'credit' THEN amount ELSE -amount END) as total")
            ->value('total');

        $this->assertSame($account->balance, (int) $ledgerSum);
    }

    /**
     * Scenario I: P12 boundary store account (-450k, limit 500k, availableCredit 50k).
     */
    public function test_scenario_i_p12_boundary_store_account(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();

        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame(MemberStoreAccountStatus::Active, $account->status);
        $this->assertSame(500000, $account->credit_limit);
        $this->assertSame(-450000, $account->balance);
        $this->assertSame(50000, $account->availableCredit());
        $this->assertTrue(abs($account->balance) <= $account->credit_limit);

        // Reconcile with ledger
        $ledgerSum = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->selectRaw("SUM(CASE WHEN effect = 'credit' THEN amount ELSE -amount END) as total")
            ->value('total');

        $this->assertSame($account->balance, (int) $ledgerSum);
    }

    /**
     * Scenario J: Store ledger immutability and idempotence.
     */
    public function test_scenario_j_store_ledger_idempotence(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);
        $initialCount = MemberStoreLedgerEntry::query()->count();

        // Rerun seeder
        $this->seed(CooperativeFinancialFixtureSeeder::class);
        $secondCount = MemberStoreLedgerEntry::query()->count();

        $this->assertSame($initialCount, $secondCount);
    }

    /**
     * Scenario K: Canonical POS products exist under KOP-001.
     */
    public function test_scenario_k_canonical_pos_products(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $headOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $products = PosProduct::query()->where('sku', 'like', 'SEED-POS-%')->get();

        $this->assertCount(4, $products);
        foreach ($products as $product) {
            $this->assertSame($headOffice->id, $product->organization_id);
            $this->assertTrue($product->is_active);
            $this->assertGreaterThan(0, $product->stock);
        }
    }

    /**
     * Scenario L: P10 routine POS transaction reconciliation.
     */
    public function test_scenario_l_p10_pos_reconciliation(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $tx = PosTransaction::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('client_reference', 'SEED-POS-REF-010-001')
            ->with(['items', 'payments'])
            ->firstOrFail();

        $this->assertSame('COMPLETED', $tx->status);
        $this->assertEquals((float) $tx->subtotal - (float) $tx->discount_amount, (float) $tx->total_amount);
        $this->assertEquals((float) $tx->payments->sum('amount'), (float) $tx->total_amount);
        $this->assertEquals((float) $tx->items->sum('line_total'), (float) $tx->subtotal);
        $this->assertEquals((float) $tx->items->sum('line_profit'), (float) $tx->gross_profit);
    }

    /**
     * Scenario M: P12 store account POS transaction reconciliation.
     */
    public function test_scenario_m_p12_store_account_pos_reconciliation(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $tx = PosTransaction::query()
            ->where('cooperative_member_id', $p12->id)
            ->where('client_reference', 'SEED-POS-REF-012-001')
            ->with(['items', 'payments'])
            ->firstOrFail();

        $this->assertSame('COMPLETED', $tx->status);
        $payment = $tx->payments->where('payment_method', 'MEMBER_STORE_ACCOUNT')->firstOrFail();
        $this->assertEquals(450000, (float) $payment->amount);

        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame(-450000, $account->balance);

        $ledgerEntry = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('reference_type', 'pos_transaction')
            ->where('reference_id', (string) $tx->id)
            ->firstOrFail();

        $this->assertSame(450000, $ledgerEntry->amount);
        $this->assertSame(MemberStoreLedgerEffect::Debit, $ledgerEntry->effect);
        $this->assertSame('Seed Member Google', $ledgerEntry->purchaser_name);
    }

    /**
     * Scenario N: P12 active loan.
     */
    public function test_scenario_n_p12_active_loan(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();
        $manajer = User::query()->where('email', 'seed.manajer@kojaya.test')->firstOrFail();
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $loan = Loan::query()
            ->where('cooperative_member_id', $p12->id)
            ->where('reference_no', 'SEED-LOAN-ACTIVE-012-001')
            ->with('installments')
            ->firstOrFail();

        $this->assertSame(LoanStatus::Active, $loan->status);
        $this->assertGreaterThan(0, (float) $loan->outstanding_amount);
        $this->assertCount(6, $loan->installments);

        // Workflow review & approval assertions
        $this->assertNotNull($loan->manager_reviewed_at);
        $this->assertSame($manajer->id, $loan->manager_reviewed_by);
        $this->assertNotNull($loan->approved_at);
        $this->assertSame($pengurus->id, $loan->approved_by);
        $this->assertNotSame($loan->manager_reviewed_by, $loan->approved_by);
        $this->assertTrue($loan->approved_at->gt($loan->manager_reviewed_at));
        $this->assertTrue($loan->disbursed_at->gte($loan->approved_at));
        $this->assertSame($pengurus->id, $loan->disbursed_by);

        // Assert at least installment 1 is PENDING
        $firstInst = $loan->installments->sortBy('installment_no')->first();
        $this->assertSame(InstallmentStatus::Pending, $firstInst->status);
        $this->assertSame('2026-06-10', $firstInst->due_date?->toDateString());
    }

    /**
     * Scenario O: P10 paid-off loan.
     */
    public function test_scenario_o_p10_paid_off_loan(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();
        $manajer = User::query()->where('email', 'seed.manajer@kojaya.test')->firstOrFail();
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $loan = Loan::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('reference_no', 'SEED-LOAN-CLOSED-010-001')
            ->with(['installments', 'payments'])
            ->firstOrFail();

        $this->assertSame(LoanStatus::PaidOff, $loan->status);
        $this->assertEquals(0, (float) $loan->outstanding_amount);
        $this->assertCount(6, $loan->installments);

        // Workflow review & approval assertions
        $this->assertNotNull($loan->manager_reviewed_at);
        $this->assertSame($manajer->id, $loan->manager_reviewed_by);
        $this->assertNotNull($loan->approved_at);
        $this->assertSame($pengurus->id, $loan->approved_by);
        $this->assertNotSame($loan->manager_reviewed_by, $loan->approved_by);
        $this->assertTrue($loan->approved_at->gt($loan->manager_reviewed_at));
        $this->assertTrue($loan->disbursed_at->gte($loan->approved_at));
        $this->assertSame($pengurus->id, $loan->disbursed_by);

        foreach ($loan->installments as $inst) {
            $this->assertSame(InstallmentStatus::Paid, $inst->status);
            $this->assertEquals((float) $inst->amount_due, (float) $inst->amount_paid);
        }

        $this->assertEquals((float) $loan->total_amount, (float) $loan->payments->sum('amount'));
    }

    /**
     * Scenario P: Loan ledger reconciliation.
     */
    public function test_scenario_p_loan_ledger_reconciliation(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $loanClosed = Loan::query()->where('reference_no', 'SEED-LOAN-CLOSED-010-001')->firstOrFail();

        // Disbursement debit matches principal
        $disburseLedger = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('source_type', Loan::class)
            ->where('source_id', $loanClosed->id)
            ->where('entry_type', 'LOAN_DISBURSEMENT')
            ->firstOrFail();
        $this->assertEquals((float) $loanClosed->principal_amount, (float) $disburseLedger->debit);

        // Payment credits match loan payments
        $paymentCredits = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('entry_type', 'LOAN_PAYMENT')
            ->sum('credit');
        $this->assertEquals((float) $loanClosed->total_amount, (float) $paymentCredits);
    }

    /**
     * Scenario Q: Complete five shapes explicit verification.
     */
    public function test_scenario_q_complete_five_shapes_verified(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();

        // 1. EMPTY -> P13
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p13->id)->count());

        // 2. NORMAL -> P10 (positive store balance, routine POS)
        $account10 = MemberStoreAccount::query()->where('cooperative_member_id', $p10->id)->firstOrFail();
        $this->assertSame(150000, $account10->balance);

        // 3. PARTIAL / UNPAID -> P12 (unpaid WAJIB, pending loan installments)
        $inv12 = CooperativeDuesInvoice::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame('UNPAID', $inv12->status);

        // 4. COMPLETED / PAID -> P10 (paid contributions, paid off loan)
        $loan10 = Loan::query()->where('cooperative_member_id', $p10->id)->firstOrFail();
        $this->assertSame(LoanStatus::PaidOff, $loan10->status);

        // 5. VALID BOUNDARY -> P12 (store balance -450k near limit 500k)
        $account12 = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame(-450000, $account12->balance);
        $this->assertSame(50000, $account12->availableCredit());
    }

    /**
     * Scenario R: Fail-closed environment guard.
     */
    public function test_scenario_r_environment_guard_aborts_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('CooperativeFinancialFixtureSeeder is only available in local, testing, or playwright environments.');

            (new CooperativeFinancialFixtureSeeder)->run();
        } finally {
            $this->app['env'] = 'testing';
            config(['app.env' => 'testing']);
        }
    }

    /**
     * Scenario S: Idempotence of repeated seeding.
     */
    public function test_scenario_s_idempotence(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $invoicesCount = CooperativeDuesInvoice::query()->count();
        $paymentsCount = CooperativePayment::query()->count();
        $receiptsCount = CooperativeReceipt::query()->count();
        $ledgerCount = CooperativeLedgerEntry::query()->count();
        $storeAccountsCount = MemberStoreAccount::query()->count();
        $storeLedgerCount = MemberStoreLedgerEntry::query()->count();
        $posTxCount = PosTransaction::query()->count();
        $loansCount = Loan::query()->count();

        // Rerun seeder
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $this->assertSame($invoicesCount, CooperativeDuesInvoice::query()->count());
        $this->assertSame($paymentsCount, CooperativePayment::query()->count());
        $this->assertSame($receiptsCount, CooperativeReceipt::query()->count());
        $this->assertSame($ledgerCount, CooperativeLedgerEntry::query()->count());
        $this->assertSame($storeAccountsCount, MemberStoreAccount::query()->count());
        $this->assertSame($storeLedgerCount, MemberStoreLedgerEntry::query()->count());
        $this->assertSame($posTxCount, PosTransaction::query()->count());
        $this->assertSame($loansCount, Loan::query()->count());
    }

    /**
     * Scenario T: Calendar independence.
     */
    public function test_scenario_t_calendar_independence(): void
    {
        // Run with clock at 2026-06-01
        Carbon::setTestNow('2026-06-01 10:00:00');
        $this->seed(CooperativeFinancialFixtureSeeder::class);
        $countJune = CooperativeDuesInvoice::query()->count();
        $loansJune = Loan::query()->count();

        // Rerun with clock at 2026-09-20
        Carbon::setTestNow('2026-09-20 15:30:00');
        $this->seed(CooperativeFinancialFixtureSeeder::class);
        $countSept = CooperativeDuesInvoice::query()->count();
        $loansSept = Loan::query()->count();

        $this->assertSame($countJune, $countSept);
        $this->assertSame($loansJune, $loansSept);
    }

    /**
     * Scenario U: Full DatabaseSeeder isolation preserves canonical financial matrix.
     */
    public function test_scenario_u_full_database_seeder_isolation(): void
    {
        $previousEnv = $this->app['env'];
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        try {
            $this->seed(DatabaseSeeder::class);
        } finally {
            $this->app['env'] = $previousEnv;
            config(['app.env' => $previousEnv]);
        }

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();

        // P13 remains strictly EMPTY
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $p13->id)->count());

        // P06-P09 remain strictly ZERO finance
        $nonActiveNos = ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'];
        $nonActiveIds = CooperativeMember::query()->whereIn('member_no', $nonActiveNos)->pluck('id')->all();
        foreach ($nonActiveIds as $mId) {
            $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $mId)->count());
            $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $mId)->count());
            $this->assertSame(0, Loan::query()->where('cooperative_member_id', $mId)->count());
        }

        // P10 has expected finance
        $this->assertTrue(MemberStoreAccount::query()->where('cooperative_member_id', $p10->id)->where('balance', 150000)->exists());
        $this->assertTrue(Loan::query()->where('cooperative_member_id', $p10->id)->where('status', LoanStatus::PaidOff)->exists());

        // P12 has expected boundary finance
        $this->assertTrue(MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->where('balance', -450000)->exists());
        $this->assertTrue(Loan::query()->where('cooperative_member_id', $p12->id)->where('status', LoanStatus::Active)->exists());
    }

    /**
     * Scenario V: Organization integrity - all canonical finance belongs to KOP-001.
     */
    public function test_scenario_v_organization_integrity(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $headOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $subsidiary = Organization::query()->where('code', 'KBU-001')->first();
        $iso = Organization::query()->where('code', 'ISO-999')->first();

        // KBU-001 must have 0 members and 0 member finance
        if ($subsidiary) {
            $this->assertSame(0, CooperativeMember::query()->where('organization_id', $subsidiary->id)->count());
            $this->assertSame(0, MemberStoreAccount::query()->where('organization_id', $subsidiary->id)->count());
            $this->assertSame(0, Loan::query()->where('organization_id', $subsidiary->id)->count());
        }

        if ($iso) {
            $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());
            $this->assertSame(0, MemberStoreAccount::query()->where('organization_id', $iso->id)->count());
            $this->assertSame(0, Loan::query()->where('organization_id', $iso->id)->count());
        }

        // Canonical financial fixtures belong to KOP-001
        $canonicalLoans = Loan::query()->where('reference_no', 'like', 'SEED-LOAN-%')->get();
        foreach ($canonicalLoans as $loan) {
            $this->assertSame($headOffice->id, $loan->organization_id);
        }

        $canonicalStoreAccounts = MemberStoreAccount::query()
            ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
            ->get();
        foreach ($canonicalStoreAccounts as $acc) {
            $this->assertSame($headOffice->id, $acc->organization_id);
        }
    }

    /**
     * Scenario W: No SEED-06 negative/edge data in baseline.
     */
    public function test_scenario_w_no_seed_06_edge_data(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        // No defaulted or written-off loans
        $this->assertSame(0, Loan::query()->whereIn('status', [LoanStatus::Defaulted, LoanStatus::WrittenOff])->count());

        // No over-limit store accounts (balance < -credit_limit)
        $this->assertSame(0, MemberStoreAccount::query()->whereRaw('balance < -credit_limit')->count());

        // No financial fixtures for P11
        $p11 = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->first();
        $this->assertNull($p11);
    }
}
