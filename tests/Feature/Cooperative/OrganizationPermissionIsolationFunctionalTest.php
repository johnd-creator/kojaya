<?php

namespace Tests\Feature\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\LoanStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\Organization;
use App\Models\PosCashierShift;
use App\Models\PosCategory;
use App\Models\PosDailyClosing;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\PosJournalPostingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationPermissionIsolationFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $adminA;

    private User $adminB;

    private User $pengurusA;

    private User $kasirA;

    private User $memberUserA;

    private User $memberUserB;

    private CooperativeMember $memberA;

    private CooperativeMember $memberB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::factory()->create([
            'name' => 'Koperasi Jaya Bersama',
            'code' => 'KOP-001',
        ]);

        $this->orgB = Organization::factory()->create([
            'name' => 'Koperasi Terisolasi',
            'code' => 'ISO-999',
        ]);

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->adminA->assignRole('Admin Koperasi');

        $this->adminB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->adminB->assignRole('Admin Koperasi');

        $this->pengurusA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->pengurusA->assignRole('Pengurus Koperasi');

        $this->kasirA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->kasirA->assignRole('Kasir Koperasi');

        $this->memberUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->memberUserA->assignRole('Anggota');
        $this->memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->memberUserA->id,
            'name' => 'Anggota Koperasi A',
            'member_no' => 'MBR-A-001',
            'no_anggota' => 'MBR-A-001',
        ]);

        $this->memberUserB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->memberUserB->assignRole('Anggota');
        $this->memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgB->id,
            'user_id' => $this->memberUserB->id,
            'name' => 'Anggota Koperasi B',
            'member_no' => 'MBR-B-001',
            'no_anggota' => 'MBR-B-001',
        ]);
    }

    // =========================================================================
    // ISO-001: Multi-Tenant Data Isolation
    // =========================================================================

    public function test_iso001_multi_tenant_data_isolation_for_all_cooperative_modules(): void
    {
        $contributionType = CooperativeContributionType::factory()->create();

        $invoiceA = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        $paymentA = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoiceA->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        $paymentB = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_dues_invoice_id' => $invoiceB->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        $loanType = LoanType::factory()->create(['is_active' => true]);
        $loanA = Loan::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'organization_id' => $this->orgA->id,
            'loan_type_id' => $loanType->id,
        ]);
        $loanB = Loan::factory()->create([
            'cooperative_member_id' => $this->memberB->id,
            'organization_id' => $this->orgB->id,
            'loan_type_id' => $loanType->id,
        ]);

        $storeAccountA = MemberStoreAccount::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'organization_id' => $this->orgA->id,
        ]);
        $storeAccountB = MemberStoreAccount::factory()->create([
            'cooperative_member_id' => $this->memberB->id,
            'organization_id' => $this->orgB->id,
        ]);

        $ledgerA = CooperativeLedgerEntry::factory()->create([
            'organization_id' => $this->orgA->id,
            'debit' => 100000,
            'credit' => 0,
        ]);
        $ledgerB = CooperativeLedgerEntry::factory()->create([
            'organization_id' => $this->orgB->id,
            'debit' => 200000,
            'credit' => 0,
        ]);

        $scopeService = app(OrganizationScopedQueryService::class);

        // 1. Scoped query for Admin Org A
        $scopedMembers = $scopeService->scopeVisibleTo(CooperativeMember::query(), $this->adminA)->pluck('id');
        $this->assertContains($this->memberA->id, $scopedMembers);
        $this->assertNotContains($this->memberB->id, $scopedMembers);

        $scopedInvoices = $scopeService->scopeVisibleTo(CooperativeDuesInvoice::query(), $this->adminA)->pluck('id');
        $this->assertContains($invoiceA->id, $scopedInvoices);
        $this->assertNotContains($invoiceB->id, $scopedInvoices);

        $scopedPayments = $scopeService->scopeVisibleTo(CooperativePayment::query(), $this->adminA)->pluck('id');
        $this->assertContains($paymentA->id, $scopedPayments);
        $this->assertNotContains($paymentB->id, $scopedPayments);

        $scopedLoans = $scopeService->scopeVisibleTo(Loan::query(), $this->adminA)->pluck('id');
        $this->assertContains($loanA->id, $scopedLoans);
        $this->assertNotContains($loanB->id, $scopedLoans);

        $scopedStore = $scopeService->scopeVisibleTo(MemberStoreAccount::query(), $this->adminA)->pluck('id');
        $this->assertContains($storeAccountA->id, $scopedStore);
        $this->assertNotContains($storeAccountB->id, $scopedStore);

        $scopedLedger = $scopeService->scopeVisibleTo(CooperativeLedgerEntry::query(), $this->adminA)->pluck('id');
        $this->assertContains($ledgerA->id, $scopedLedger);
        $this->assertNotContains($ledgerB->id, $scopedLedger);

        // 2. Global user (Pengurus with view_cooperative_all) sees both
        $globalMembers = $scopeService->scopeVisibleTo(CooperativeMember::query(), $this->pengurusA)->pluck('id');
        $this->assertContains($this->memberA->id, $globalMembers);
        $this->assertContains($this->memberB->id, $globalMembers);

        // 3. User with no organization and no global permission fails closed
        $unattachedUser = User::factory()->create(['organization_id' => null]);
        $this->expectException(AuthorizationException::class);
        $scopeService->scopeVisibleTo(CooperativeMember::query(), $unattachedUser);
    }

    // =========================================================================
    // ISO-002: Subsidiary Branch (KBU-001) Zero Member Invariant
    // =========================================================================

    public function test_iso002_subsidiary_kbu001_zero_member_and_zero_finance_invariant(): void
    {
        $kbu = Organization::factory()->create([
            'name' => 'PT Koperasi Berkah Usaha',
            'code' => 'KBU-001',
            'type' => 'BRANCH',
        ]);

        // Invariant: Subsidiary company KBU-001 must have strictly 0 CooperativeMembers
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kbu->id)->count());

        // Invariant: KBU-001 must have 0 cooperative financial ledger entries
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('organization_id', $kbu->id)->count());

        // Staff assigned to KBU-001 cannot view KOP-001 members
        $kbuStaff = User::factory()->create(['organization_id' => $kbu->id]);
        $kbuStaff->assignRole('Admin Koperasi');

        $scopeService = app(OrganizationScopedQueryService::class);
        $kbuMembers = $scopeService->scopeVisibleTo(CooperativeMember::query(), $kbuStaff)->get();
        $this->assertCount(0, $kbuMembers);
    }

    // =========================================================================
    // ISO-003: Cross-Tenant Mutation Rejection & Zero Mutation Standard
    // =========================================================================

    public function test_iso003_cross_tenant_mutation_rejection_and_zero_mutation_standard(): void
    {
        $contributionType = CooperativeContributionType::factory()->create();
        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 75000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        $paymentB = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_dues_invoice_id' => $invoiceB->id,
            'amount' => 75000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $loanType = LoanType::factory()->create(['is_active' => true]);
        $loanB = Loan::factory()->create([
            'cooperative_member_id' => $this->memberB->id,
            'organization_id' => $this->orgB->id,
            'loan_type_id' => $loanType->id,
            'status' => LoanStatus::Active->value,
            'principal_amount' => 5000000,
        ]);

        $initialPaymentsCount = CooperativePayment::count();
        $initialLedgerCount = CooperativeLedgerEntry::count();

        // 1. Admin A attempts to approve payment from Org B -> Rejected with 403
        $responsePayment = $this->actingAs($this->adminA)
            ->post(route('cooperative.payments.approve', ['payment' => $paymentB->id]));
        $responsePayment->assertForbidden();

        // Verify Zero Mutation: payment remains PENDING, no receipt, no ledger entry
        $paymentB->refresh();
        $this->assertSame('PENDING', $paymentB->status);
        $this->assertNull($paymentB->receipt_no);
        $this->assertSame($initialLedgerCount, CooperativeLedgerEntry::count());

        // 2. Admin A attempts to update member from Org B via direct ID -> Rejected (404/403)
        $responseMemberUpdate = $this->actingAs($this->adminA)
            ->put(route('cooperative.members.update', ['member' => $this->memberB->id]), [
                'nama_anggota' => 'Tampered Member B',
                'name' => 'Tampered Member B',
                'email' => 'tampered@example.com',
                'phone' => '08129999999',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ]);
        $this->assertTrue(in_array($responseMemberUpdate->getStatusCode(), [403, 404], true));
        $this->assertSame('Anggota Koperasi B', $this->memberB->fresh()->name);

        // 3. Admin A attempts to write-off loan from Org B -> Rejected (403/404)
        $responseWriteOff = $this->actingAs($this->adminA)
            ->post(route('cooperative.loans.write-off', ['loan' => $loanB->id]), [
                'reason' => 'Cross-tenant illegal write-off attempt',
            ]);
        $this->assertTrue(in_array($responseWriteOff->getStatusCode(), [403, 404], true));

        // Verify Zero Mutation: loan remains ACTIVE, no LOAN_WRITE_OFF ledger
        $loanB->refresh();
        $this->assertSame(LoanStatus::Active, $loanB->status);
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'source_id' => $loanB->id,
            'entry_type' => 'LOAN_WRITE_OFF',
        ]);
        $this->assertSame($initialLedgerCount, CooperativeLedgerEntry::count());
    }

    // =========================================================================
    // ISO-004: POS Product & Category Tenant Scoping
    // =========================================================================

    public function test_iso004_pos_product_and_category_tenant_scoping_and_barcode_collision(): void
    {
        $categoryA = PosCategory::factory()->create(['organization_id' => $this->orgA->id, 'name' => 'Minuman Org A']);
        $categoryB = PosCategory::factory()->create(['organization_id' => $this->orgB->id, 'name' => 'Minuman Org B']);

        $productA = PosProduct::factory()->create([
            'organization_id' => $this->orgA->id,
            'pos_category_id' => $categoryA->id,
            'barcode' => '8999999000123',
            'name' => 'Kopi A',
            'stock' => 20,
            'sale_price' => 15000,
        ]);

        $productB = PosProduct::factory()->create([
            'organization_id' => $this->orgB->id,
            'pos_category_id' => $categoryB->id,
            'barcode' => '8999999000456',
            'name' => 'Kopi B',
            'stock' => 50,
            'sale_price' => 25000,
        ]);

        // Admin Org A only sees products from Org A in index
        $indexResponse = $this->actingAs($this->adminA)->get(route('cooperative.pos-products.index'));
        $indexResponse->assertOk();
        $this->assertStringContainsString('Kopi A', $indexResponse->getContent());
        $this->assertStringNotContainsString('Kopi B', $indexResponse->getContent());

        // Admin Org A cannot view Product B
        $this->actingAs($this->adminA)
            ->get(route('cooperative.pos-products.show', ['product' => $productB->id]))
            ->assertForbidden();

        // Kasir A cannot create POS transaction with Product B
        $response = $this->actingAs($this->kasirA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-ISO4-FAIL',
            'items' => [
                ['pos_product_id' => $productB->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 25000, 'cash_received' => 25000],
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 302, 422], true));
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-ISO4-FAIL']);
        $this->assertSame(50, (int) $productB->fresh()->stock);
    }

    // =========================================================================
    // ISO-005: Store Credit Cross-Tenant Boundary
    // =========================================================================

    public function test_iso005_store_credit_cross_tenant_boundary(): void
    {
        $storeAccountB = MemberStoreAccount::factory()->create([
            'cooperative_member_id' => $this->memberB->id,
            'organization_id' => $this->orgB->id,
            'balance' => 0,
            'credit_limit' => 1000000,
        ]);

        // Kasir Org A cannot view store account of Org B
        $responseShow = $this->actingAs($this->kasirA)
            ->get(route('cooperative.store-credit.show', ['account' => $storeAccountB->id]));
        $this->assertTrue(in_array($responseShow->getStatusCode(), [403, 404], true));

        // Kasir Org A cannot perform cash funding on Org B store account
        $responseCashFund = $this->actingAs($this->kasirA)
            ->post(route('cooperative.store-credit.cash-funding', ['account' => $storeAccountB->id]), [
                'amount' => 200000,
                'idempotency_key' => 'ISO5-FUND-'.uniqid(),
            ]);
        $this->assertTrue(in_array($responseCashFund->getStatusCode(), [403, 404], true));

        // Verify zero balance mutation
        $storeAccountB->refresh();
        $this->assertSame(0, (int) $storeAccountB->balance);

        // Admin Org A cannot add delegate to Org B store account
        $responseDelegate = $this->actingAs($this->adminA)
            ->post(route('cooperative.store-credit.delegates.store', ['account' => $storeAccountB->id]), [
                'display_name' => 'Penyusup Org A',
                'relationship' => 'Istri',
            ]);
        $this->assertTrue(in_array($responseDelegate->getStatusCode(), [403, 404], true));
        $this->assertSame(0, MemberStoreDelegate::query()->where('account_id', $storeAccountB->id)->count());
    }

    // =========================================================================
    // ISO-006: Member Portal Self-Service Tenant Boundary
    // =========================================================================

    public function test_iso006_member_portal_self_service_tenant_boundary(): void
    {
        $contributionType = CooperativeContributionType::factory()->create();
        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        $loanType = LoanType::factory()->create(['is_active' => true]);
        $loanB = Loan::factory()->create([
            'cooperative_member_id' => $this->memberB->id,
            'organization_id' => $this->orgB->id,
            'loan_type_id' => $loanType->id,
            'status' => LoanStatus::Active->value,
            'principal_amount' => 3000000,
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // 1. Member A cannot view Member B's invoice (returns 403 or 404)
        $invoiceRes = $this->getJson("/api/v1/member/dues/invoices/{$invoiceB->id}");
        $this->assertTrue(in_array($invoiceRes->status(), [403, 404], true));

        // 2. Member A cannot view Member B's loan details (returns 403 or 404)
        $loanRes = $this->getJson("/api/v1/member/loans/{$loanB->id}");
        $this->assertTrue(in_array($loanRes->status(), [403, 404], true));

        // 3. Member A cannot upload payment proof for Member B's invoice
        $file = UploadedFile::fake()->image('bukti.jpg');
        $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoiceB->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'proof' => $file,
        ])->assertStatus(404);

        // Zero payment records created
        $this->assertDatabaseMissing('cooperative_payments', [
            'cooperative_dues_invoice_id' => $invoiceB->id,
        ]);
    }

    // =========================================================================
    // ISO-007: Staff Role Privilege Escalation Defense
    // =========================================================================

    public function test_iso007_staff_role_privilege_escalation_defense(): void
    {
        $loanType = LoanType::factory()->create(['is_active' => true]);
        $loanA = Loan::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'organization_id' => $this->orgA->id,
            'loan_type_id' => $loanType->id,
            'status' => LoanStatus::Active->value,
            'principal_amount' => 2000000,
        ]);

        $storeAccountA = MemberStoreAccount::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'organization_id' => $this->orgA->id,
            'credit_limit' => 500000,
            'balance' => 0,
        ]);

        $entryA = CooperativeLedgerEntry::factory()->create([
            'organization_id' => $this->orgA->id,
            'debit' => 50000,
            'credit' => 0,
            'entry_type' => 'MANUAL',
        ]);

        // 1. Admin Koperasi cannot approve loan (Pengurus-only)
        $responseApprove = $this->actingAs($this->adminA)
            ->post(route('cooperative.loans.approve', ['loan' => $loanA->id]));
        $responseApprove->assertForbidden();

        // 2. Admin Koperasi cannot write off loan (Pengurus-only)
        $responseWriteOff = $this->actingAs($this->adminA)
            ->post(route('cooperative.loans.write-off', ['loan' => $loanA->id]), [
                'reason' => 'Unauthorized write-off',
            ]);
        $responseWriteOff->assertForbidden();

        // 3. Admin Koperasi cannot change store credit limit (Pengurus-only)
        $responseLimit = $this->actingAs($this->adminA)
            ->post(route('cooperative.store-credit.limit', ['account' => $storeAccountA->id]), [
                'credit_limit' => 10000000,
            ]);
        $responseLimit->assertForbidden();
        $this->assertSame(500000, (int) $storeAccountA->fresh()->credit_limit);

        // 4. Admin Koperasi cannot cancel/revise cooperative ledger entries
        $responseCancelLedger = $this->actingAs($this->adminA)
            ->post(route('cooperative.ledger.cancel-payment', ['entry' => $entryA->id]), [
                'reason' => 'Unauthorized cancel attempt',
            ]);
        $responseCancelLedger->assertForbidden();
    }

    // =========================================================================
    // ISO-008: POS Void & Daily Closing Isolation
    // =========================================================================

    public function test_iso008_pos_void_and_daily_closing_isolation(): void
    {
        $categoryB = PosCategory::factory()->create(['organization_id' => $this->orgB->id]);
        $productB = PosProduct::factory()->create([
            'organization_id' => $this->orgB->id,
            'pos_category_id' => $categoryB->id,
            'sale_price' => 20000,
            'stock' => 10,
        ]);

        $txB = PosTransaction::query()->create([
            'organization_id' => $this->orgB->id,
            'transaction_no' => 'TRX-B-'.uniqid(),
            'sold_at' => now(),
            'status' => 'COMPLETED',
            'subtotal' => 20000,
            'total_amount' => 20000,
            'grand_total' => 20000,
        ]);

        $closingB = PosDailyClosing::query()->create([
            'organization_id' => $this->orgB->id,
            'closing_date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        // Kasir/Manajer Org A cannot void PosTransaction from Org B
        $responseVoid = $this->actingAs($this->adminA)
            ->post(route('cooperative.pos.void-requests.store', ['transaction' => $txB->id]), [
                'reason' => 'Illegal cross-org void',
            ]);
        $this->assertTrue(in_array($responseVoid->getStatusCode(), [403, 404], true));
        $this->assertSame('COMPLETED', $txB->fresh()->status);

        // Daily closing summary for Org A excludes Org B transactions
        $closingSummaryA = app(\App\Services\Cooperative\PosDailyClosingService::class)
            ->summaryForDate(now()->toDateString(), $this->orgA->id);
        $this->assertSame(0, $closingSummaryA['transaction_count']);

        // And scoped query on PosDailyClosing for Admin A excludes Org B closing
        $scopedClosings = app(OrganizationScopedQueryService::class)
            ->scopeVisibleTo(PosDailyClosing::query(), $this->adminA)
            ->pluck('id');
        $this->assertNotContains($closingB->id, $scopedClosings);
    }

    // =========================================================================
    // SECTION 14: POS_SHIFT_DIFF Fail-Closed Invariant
    // =========================================================================

    public function test_pos_shift_diff_fail_closed_edge_cases_a_b_c_d(): void
    {
        $postingService = app(PosJournalPostingService::class);

        $shiftA = PosCashierShift::query()->create([
            'shift_no' => 'SHIFT-A-'.uniqid(),
            'shift_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => 50000,
            'cashier_id' => $this->kasirA->id,
            'status' => 'OPEN',
        ]);

        // Case A: Explicit organization_id provided
        $entryA = $postingService->postShiftDifference($shiftA->id, -10000.0, (string) $this->orgA->id);
        $this->assertNotNull($entryA);
        $this->assertSame((string) $this->orgA->id, (string) $entryA->organization_id);
        $this->assertSame(10000.0, (float) $entryA->debit);

        $unattachedCashier = User::factory()->create(['organization_id' => null]);

        // Case B: Derived from shift transactions (cashier has no org)
        $shiftB = PosCashierShift::query()->create([
            'shift_no' => 'SHIFT-B-'.uniqid(),
            'shift_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => 50000,
            'cashier_id' => $unattachedCashier->id,
            'status' => 'OPEN',
        ]);
        PosTransaction::query()->create([
            'organization_id' => $this->orgB->id,
            'pos_cashier_shift_id' => $shiftB->id,
            'transaction_no' => 'TRX-B-SHIFT',
            'sold_at' => now(),
            'status' => 'COMPLETED',
            'subtotal' => 50000,
            'total_amount' => 50000,
            'grand_total' => 50000,
        ]);
        $entryB = $postingService->postShiftDifference($shiftB->id, 15000.0);
        $this->assertNotNull($entryB);
        $this->assertSame((string) $this->orgB->id, (string) $entryB->organization_id);
        $this->assertSame(15000.0, (float) $entryB->credit);

        // Case C: Derived from cashier's organization
        $shiftC = PosCashierShift::query()->create([
            'shift_no' => 'SHIFT-C-'.uniqid(),
            'shift_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => 50000,
            'cashier_id' => $this->kasirA->id,
            'status' => 'OPEN',
        ]);
        $entryC = $postingService->postShiftDifference($shiftC->id, -5000.0);
        $this->assertNotNull($entryC);
        $this->assertSame((string) $this->orgA->id, (string) $entryC->organization_id);

        // Case D: Unresolvable organization -> FAIL CLOSED: returns null, creates ZERO rows!
        $shiftD = PosCashierShift::query()->create([
            'shift_no' => 'SHIFT-D-ORPHAN',
            'shift_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => 50000,
            'cashier_id' => $unattachedCashier->id,
            'status' => 'OPEN',
        ]);
        $initialDiffCount = CooperativeLedgerEntry::query()->where('entry_type', 'POS_SHIFT_DIFF')->count();

        $entryD = $postingService->postShiftDifference($shiftD->id, -20000.0);

        $this->assertNull($entryD, 'Unresolvable organization shift difference must return null (fail-closed).');
        $this->assertSame(
            $initialDiffCount,
            CooperativeLedgerEntry::query()->where('entry_type', 'POS_SHIFT_DIFF')->count(),
            'Unresolvable shift difference must create strictly ZERO ledger entries.'
        );
    }

    // =========================================================================
    // PAY-006: Private Disk Storage & Authorized Proof Retrieval
    // =========================================================================

    public function test_pay006_payment_proof_storage_and_authorized_download_access(): void
    {
        $proofDisk = config('filesystems.payment_proof_disk', 'local');
        Storage::fake($proofDisk);
        Storage::fake('public');

        $contributionType = CooperativeContributionType::factory()->create();
        $invoiceA = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        // 1. Member A uploads proof via API
        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);
        $file = UploadedFile::fake()->image('bukti_setoran.png', 400, 400);

        $uploadResponse = $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoiceA->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'proof' => $file,
        ])->assertCreated();

        $paymentId = $uploadResponse->json('data.id');
        $paymentA = CooperativePayment::query()->findOrFail($paymentId);

        // Assert file stored on PRIVATE disk, NOT accessible on public disk
        Storage::disk($proofDisk)->assertExists($paymentA->proof_path);
        Storage::disk('public')->assertMissing($paymentA->proof_path);

        // 2. Member A (owner) can download proof via API endpoint
        $memberDownload = $this->get("/api/v1/member/payments/{$paymentA->id}/proof");
        $memberDownload->assertOk();
        $this->assertStringContainsString('attachment', $memberDownload->headers->get('Content-Disposition') ?? '');

        // 3. Member B is denied download of Member A's proof via API
        Sanctum::actingAs($this->memberUserB, ['member:read']);
        $this->get("/api/v1/member/payments/{$paymentA->id}/proof")->assertForbidden();

        // 4. Staff from Org A can download proof via staff route
        $staffDownload = $this->actingAs($this->adminA)
            ->get(route('cooperative.payments.proof', ['payment' => $paymentA->id]));
        $staffDownload->assertOk();

        // 5. Staff from Org B is denied download of Org A's proof
        $crossStaffDownload = $this->actingAs($this->adminB)
            ->get(route('cooperative.payments.proof', ['payment' => $paymentA->id]));
        $crossStaffDownload->assertForbidden();

        // 6. Unauthenticated request is rejected
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $guestDownload = $this->get(route('cooperative.payments.proof', ['payment' => $paymentA->id]));
        $this->assertTrue(in_array($guestDownload->getStatusCode(), [302, 401], true));

        // 7. Migrate a legacy public proof before authorized retrieval.
        $legacyPath = 'cooperative/payment-proofs/legacy.png';
        Storage::disk('public')->put($legacyPath, 'legacy-content');
        $legacyPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoiceA->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'proof_path' => $legacyPath,
        ]);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertSuccessful();

        Storage::disk($proofDisk)->assertExists($legacyPath);
        Storage::disk('public')->assertMissing($legacyPath);

        $legacyDownload = $this->actingAs($this->adminA)
            ->get(route('cooperative.payments.proof', ['payment' => $legacyPayment->id]));
        $legacyDownload->assertOk();

        Sanctum::actingAs($this->memberUserA, ['member:read']);
        $this->get("/api/v1/member/payments/{$legacyPayment->id}/proof")->assertOk();

        $this->actingAs($this->memberUserA)
            ->get("/member/payments/{$legacyPayment->id}/proof")
            ->assertOk();

        // 8. Missing proof file produces 404
        $missingProofPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoiceA->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'proof_path' => 'cooperative/payment-proofs/non-existent.png',
        ]);
        $this->actingAs($this->adminA)
            ->get(route('cooperative.payments.proof', ['payment' => $missingProofPayment->id]))
            ->assertNotFound();
    }

    public function test_pay006_disabled_legacy_fallback_does_not_serve_a_public_only_proof(): void
    {
        $proofDisk = config('filesystems.payment_proof_disk', 'local');
        Storage::fake($proofDisk);
        Storage::fake('public');
        config()->set('filesystems.payment_proof_legacy_public_fallback', false);

        $contributionType = CooperativeContributionType::factory()->create();
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);
        $path = 'cooperative/payment-proofs/legacy-fallback-disabled.png';
        Storage::disk('public')->put($path, 'legacy-public-proof');
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'proof_path' => $path,
        ]);

        $this->actingAs($this->adminA)
            ->get(route('cooperative.payments.proof', ['payment' => $payment->id]))
            ->assertNotFound();

        Storage::disk('public')->assertExists($path);
        Storage::disk($proofDisk)->assertMissing($path);
    }
}
