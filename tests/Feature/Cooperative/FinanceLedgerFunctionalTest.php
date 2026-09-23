<?php

namespace Tests\Feature\Cooperative;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\WithdrawalStatus;
use App\Models\ApprovalLog;
use App\Models\AuditLog;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\CooperativeMemberOpeningBalanceLine;
use App\Models\CooperativePayment;
use App\Models\CooperativePeriodLock;
use App\Models\CooperativeShuAllocation;
use App\Models\CooperativeShuPeriod;
use App\Models\Organization;
use App\Models\SavingsWithdrawal;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceLedgerFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $systemAdmin;

    private User $pengurusA;

    private User $pengurusB;

    private User $manajerB;

    private User $adminKoperasiA;

    private User $kasirA;

    private User $unauthorizedUser;

    private CooperativeContributionType $pokok;

    private CooperativeContributionType $wajib;

    private CooperativeContributionType $sukarela;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->orgA = Organization::factory()->create(['name' => 'Koperasi Unit A']);
        $this->orgB = Organization::factory()->create(['name' => 'Koperasi Unit B']);

        $this->systemAdmin = User::factory()->create([
            'name' => 'System Admin Global',
            'organization_id' => null,
        ]);
        $this->systemAdmin->assignRole('System Admin');

        $this->pengurusA = User::factory()->create([
            'name' => 'Pengurus Unit A',
            'organization_id' => $this->orgA->id,
        ]);
        $this->pengurusA->assignRole('Pengurus Koperasi');

        $this->pengurusB = User::factory()->create([
            'name' => 'Pengurus Unit B',
            'organization_id' => $this->orgB->id,
        ]);
        $this->pengurusB->assignRole('Pengurus Koperasi');

        $this->manajerB = User::factory()->create([
            'name' => 'Manajer Unit B',
            'organization_id' => $this->orgB->id,
        ]);
        $this->manajerB->assignRole('Manajer Koperasi');

        $this->adminKoperasiA = User::factory()->create([
            'name' => 'Admin Koperasi Unit A',
            'organization_id' => $this->orgA->id,
        ]);
        $this->adminKoperasiA->assignRole('Admin Koperasi');

        $this->kasirA = User::factory()->create([
            'name' => 'Kasir Unit A',
            'organization_id' => $this->orgA->id,
        ]);
        $this->kasirA->assignRole('Kasir Koperasi');

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Anggota Biasa',
            'organization_id' => $this->orgA->id,
        ]);
        $this->unauthorizedUser->assignRole('Anggota');

        $this->pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->sukarela = CooperativeContributionType::query()->create([
            'code' => 'SUKARELA',
            'name' => 'Simpanan Sukarela',
            'category' => 'SUKARELA',
            'default_amount' => 0,
            'frequency' => 'ADHOC',
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // FIN-001: General Ledger Listing, Filtering, and Scoped Summary
    // =========================================================================

    public function test_fin001_general_ledger_listing_filters_and_scoped_summary(): void
    {
        $memberA1 = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'member_no' => 'MBR-A1',
            'name' => 'Member A1',
        ]);
        $memberA2 = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'member_no' => 'MBR-A2',
            'name' => 'Member A2',
        ]);
        $memberB1 = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgB->id,
            'member_no' => 'MBR-B1',
            'name' => 'Member B1 Foreign',
        ]);

        // Member A1: Pokok + Wajib
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $memberA1->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'cooperative_contribution_type_id' => $this->pokok->id,
            'category_snapshot' => 'POKOK',
            'credit' => 200000,
            'debit' => 0,
            'period' => '2026-01',
            'posted_at' => '2026-01-10',
            'description' => 'Saldo awal pokok A1',
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $memberA1->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $this->wajib->id,
            'category_snapshot' => 'WAJIB',
            'credit' => 50000,
            'debit' => 0,
            'period' => '2026-02',
            'posted_at' => '2026-02-15',
            'description' => 'Iuran wajib Feb A1',
        ]);

        // Member A2: Sukarela credit and withdrawal debit
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $memberA2->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'category_snapshot' => 'SUKARELA',
            'credit' => 300000,
            'debit' => 0,
            'period' => '2026-03',
            'posted_at' => '2026-03-01',
            'description' => 'Setoran sukarela A2',
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $memberA2->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_WITHDRAWAL',
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'category_snapshot' => 'SUKARELA',
            'credit' => 0,
            'debit' => 100000,
            'period' => '2026-03',
            'posted_at' => '2026-03-15',
            'description' => 'Penarikan sukarela A2',
        ]);

        // Foreign Member B1: entries in Org B
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $memberB1->id,
            'organization_id' => $this->orgB->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'category_snapshot' => 'SUKARELA',
            'credit' => 999999,
            'debit' => 0,
            'period' => '2026-03',
            'posted_at' => '2026-03-20',
            'description' => 'Foreign entry Org B',
        ]);

        // 1. Unfiltered query by Admin Koperasi A (scoped to Org A, lacks view_cooperative_all):
        // Only Org A entries returned, Org B strictly excluded
        $response = $this->actingAs($this->adminKoperasiA)->get(route('cooperative.ledger.index'));
        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->component('Cooperative/Ledger/Index')
                ->has('entries.data', 4)
                ->where('summary.total_balance', 450000)
                ->where('summary.by_category.POKOK', 200000)
                ->where('summary.by_category.WAJIB', 50000)
                ->where('summary.by_category.SUKARELA', 200000);
        });

        // 2. Filter by member_id
        $filteredMember = $this->actingAs($this->adminKoperasiA)->get(route('cooperative.ledger.index', [
            'member_id' => $memberA1->id,
        ]));
        $filteredMember->assertOk();
        $filteredMember->assertInertia(function ($page) {
            $page->has('entries.data', 2)
                ->where('summary.total_balance', 250000)
                ->where('summary.by_category.POKOK', 200000)
                ->where('summary.by_category.WAJIB', 50000);
        });

        // 3. Filter by category
        $filteredCategory = $this->actingAs($this->adminKoperasiA)->get(route('cooperative.ledger.index', [
            'category' => 'SUKARELA',
        ]));
        $filteredCategory->assertOk();
        $filteredCategory->assertInertia(function ($page) {
            $page->has('entries.data', 2)
                ->where('summary.total_balance', 200000)
                ->where('summary.by_category.SUKARELA', 200000);
        });

        // 4. Filter by entry_type
        $filteredEntryType = $this->actingAs($this->adminKoperasiA)->get(route('cooperative.ledger.index', [
            'entry_type' => 'SAVING_WITHDRAWAL',
        ]));
        $filteredEntryType->assertOk();
        $filteredEntryType->assertInertia(function ($page) {
            $page->has('entries.data', 1)
                ->where('summary.total_balance', -100000);
        });

        // 5. Filter by date range
        $filteredDate = $this->actingAs($this->adminKoperasiA)->get(route('cooperative.ledger.index', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]));
        $filteredDate->assertOk();
        $filteredDate->assertInertia(function ($page) {
            $page->has('entries.data', 1)
                ->where('summary.total_balance', 50000)
                ->where('summary.by_category.WAJIB', 50000);
        });

        // 6. Global administrator sees all entries across organizations
        $globalResponse = $this->actingAs($this->systemAdmin)->get(route('cooperative.ledger.index'));
        $globalResponse->assertOk();
        $globalResponse->assertInertia(function ($page) {
            $page->has('entries.data', 5)
                ->where('summary.total_balance', 1449999)
                ->where('summary.by_category.SUKARELA', 1199999);
        });

        // 7. Unauthorized user without view_cooperative_ledger gets 403 Forbidden
        $this->actingAs($this->unauthorizedUser)
            ->get(route('cooperative.ledger.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // FIN-002: Payment Cancellation from Ledger
    // =========================================================================

    public function test_fin002_system_admin_can_cancel_payment_with_invoice_restoration_and_audit(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'period' => '2026-05',
            'amount' => 150000,
            'paid_amount' => 150000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'user_id' => $this->systemAdmin->id,
            'amount' => 150000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
            'receipt_no' => 'RC-202605-FIN002',
            'receipt_issued_at' => '2026-05-10 10:00:00',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'credit' => 150000,
            'debit' => 0,
            'period' => '2026-05',
            'description' => 'Setoran sukarela untuk dibatalkan',
            'posted_at' => '2026-05-10',
        ]);

        $cancelReason = 'Pembayaran dibatalkan karena kesalahan input kasir.';

        $response = $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.cancel-payment', $entry), [
                'reason' => $cancelReason,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Payment marked VOID, receipt cleared
        $payment->refresh();
        $this->assertSame('VOID', $payment->status);
        $this->assertNull($payment->receipt_no);
        $this->assertNull($payment->receipt_issued_at);
        $this->assertStringContainsString($cancelReason, (string) $payment->notes);

        // Invoice status restored to UNPAID and paid_amount decremented
        $invoice->refresh();
        $this->assertSame('UNPAID', $invoice->status);
        $this->assertSame('0.00', $invoice->paid_amount);

        // Ledger entry deleted (established product design)
        $this->assertDatabaseMissing('cooperative_ledger_entries', ['id' => $entry->id]);

        // ApprovalLog recorded
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => CooperativePayment::class,
            'subject_id' => (string) $payment->id,
            'from_status' => 'APPROVED',
            'to_status' => 'VOID',
            'approved_by' => $this->systemAdmin->id,
            'note' => $cancelReason,
        ]);

        // AuditLog recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.cancelled',
            'subject_type' => CooperativePayment::class,
            'subject_id' => (string) $payment->id,
            'user_id' => $this->systemAdmin->id,
            'reason' => $cancelReason,
        ]);

        $audit = AuditLog::query()
            ->where('action', 'payment.cancelled')
            ->where('subject_id', (string) $payment->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('APPROVED', $audit->old_values['status'] ?? null);
        $this->assertSame(150000.0, (float) ($audit->old_values['amount'] ?? 0));
        $this->assertSame('VOID', $audit->new_values['status'] ?? null);
    }

    public function test_fin002_payment_cancellation_guards_and_zero_mutation(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'user_id' => $this->systemAdmin->id,
            'amount' => 100000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'credit' => 100000,
            'debit' => 0,
            'period' => '2026-05',
            'description' => 'Test payment',
            'posted_at' => '2026-05-10',
        ]);

        // Guard 1: Non-System Admin (Admin Koperasi) cannot cancel
        $this->actingAs($this->adminKoperasiA)
            ->post(route('cooperative.ledger.cancel-payment', $entry), ['reason' => 'Admin mencoba'])
            ->assertForbidden();

        // Guard 2: Non-System Admin (Pengurus) cannot cancel
        $this->actingAs($this->pengurusA)
            ->post(route('cooperative.ledger.cancel-payment', $entry), ['reason' => 'Pengurus mencoba'])
            ->assertForbidden();

        // Guard 3: Reconciled payment cannot be cancelled
        $payment->update(['reconciled_at' => now(), 'reconciliation_reference' => 'REC-001']);
        $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.cancel-payment', $entry), ['reason' => 'Coba batalkan reconciled'])
            ->assertSessionHasErrors();

        $payment->update(['reconciled_at' => null, 'reconciliation_reference' => null]);

        // Guard 4: Locked period cannot be cancelled
        CooperativePeriodLock::query()->create([
            'period' => '2026-05',
            'module' => 'COOPERATIVE',
            'status' => 'LOCKED',
            'locked_at' => now(),
        ]);

        $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.cancel-payment', $entry), ['reason' => 'Coba batalkan saat locked'])
            ->assertSessionHasErrors();

        // Assert zero mutation: payment remains APPROVED, ledger entry exists, invoice PAID
        $this->assertSame('APPROVED', $payment->refresh()->status);
        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('100000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('cooperative_ledger_entries', ['id' => $entry->id]);
    }

    // =========================================================================
    // FIN-003: Payment Revision from Ledger
    // =========================================================================

    public function test_fin003_system_admin_can_revise_payment_with_audit_trail(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'period' => '2026-05',
            'amount' => 200000,
            'paid_amount' => 50000,
            'status' => 'PARTIAL',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'user_id' => $this->systemAdmin->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
            'receipt_no' => 'RC-OLD-001',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $this->sukarela->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'credit' => 50000,
            'debit' => 0,
            'period' => '2026-05',
            'description' => 'Setoran awal',
            'posted_at' => '2026-05-10',
        ]);

        $revisionReason = 'Penyesuaian bukti transfer sebenarnya Rp 100.000';

        $response = $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 100000,
                'payment_method' => 'TRANSFER',
                'paid_at' => '2026-05-12',
                'notes' => 'Koreksi mutasi transfer',
                'reason' => $revisionReason,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Payment updated in-place
        $payment->refresh();
        $this->assertSame('100000.00', $payment->amount);
        $this->assertSame('TRANSFER', $payment->payment_method);
        $this->assertSame('2026-05-12', $payment->paid_at->toDateString());
        $this->assertSame('Koreksi mutasi transfer', $payment->notes);

        // Invoice paid_amount adjusted by delta (+50,000 => 100,000)
        $invoice->refresh();
        $this->assertSame('100000.00', $invoice->paid_amount);

        // Ledger entry updated in-place
        $entry->refresh();
        $this->assertSame('100000.00', $entry->credit);
        $this->assertSame('2026-05-12', $entry->posted_at->toDateString());
        $this->assertSame('Koreksi mutasi transfer', $entry->description);

        // Receipt reissued
        $this->assertDatabaseHas('cooperative_receipts', ['cooperative_payment_id' => $payment->id]);

        // ApprovalLog recorded
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => CooperativePayment::class,
            'subject_id' => (string) $payment->id,
            'from_status' => 'APPROVED',
            'to_status' => 'APPROVED',
            'approved_by' => $this->systemAdmin->id,
        ]);

        // AuditLog recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.revised',
            'subject_type' => CooperativePayment::class,
            'subject_id' => (string) $payment->id,
            'user_id' => $this->systemAdmin->id,
            'reason' => $revisionReason,
        ]);

        $audit = AuditLog::query()
            ->where('action', 'payment.revised')
            ->where('subject_id', (string) $payment->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(50000.0, (float) ($audit->old_values['amount'] ?? 0));
        $this->assertSame(100000.0, (float) ($audit->new_values['amount'] ?? 0));
        $this->assertSame('TRANSFER', $audit->new_values['payment_method'] ?? null);
    }

    public function test_fin003_payment_revision_guards_and_zero_mutation(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $this->wajib->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->wajib->id,
            'user_id' => $this->systemAdmin->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $this->wajib->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'credit' => 50000,
            'debit' => 0,
            'period' => '2026-05',
            'description' => 'Setoran wajib',
            'posted_at' => '2026-05-10',
        ]);

        // Guard 1: Non-System Admin (Admin Koperasi) gets 403
        $this->actingAs($this->adminKoperasiA)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 50000,
                'payment_method' => 'CASH',
                'paid_at' => '2026-05-11',
                'reason' => 'Admin coba koreksi',
            ])
            ->assertForbidden();

        // Guard 2: Fixed amount violation (Simpanan Wajib must be exact 50,000)
        $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 60000,
                'payment_method' => 'CASH',
                'paid_at' => '2026-05-11',
                'reason' => 'Koreksi ke nominal yang salah untuk WAJIB',
            ])
            ->assertSessionHasErrors(['amount']);

        // Guard 3: Non-positive amount
        $this->actingAs($this->systemAdmin)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 0,
                'payment_method' => 'CASH',
                'paid_at' => '2026-05-11',
                'reason' => 'Nominal nol',
            ])
            ->assertSessionHasErrors(['amount']);

        // Assert zero mutation
        $this->assertSame('50000.00', $payment->refresh()->amount);
        $this->assertSame('50000.00', $entry->refresh()->credit);
        $this->assertSame('50000.00', $invoice->refresh()->paid_amount);
    }

    // =========================================================================
    // FIN-004: Opening Balance Wizard Preview & Draft Persistence
    // =========================================================================

    public function test_fin004_opening_balance_wizard_preview_and_draft_without_ledger_mutation(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'tanggal_aktif' => '2025-01-15',
        ]);

        // 1. Wizard Show page
        $this->actingAs($this->pengurusA)
            ->get(route('cooperative.members.opening-balance.show', $member))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cooperative/Members/OpeningBalance/Wizard')
                ->has('member')
                ->has('contribution_types'));

        // 2. Preview endpoint returns calculations without persisting any DB records
        $previewResponse = $this->actingAs($this->pengurusA)
            ->postJson(route('cooperative.members.opening-balance.preview', $member), [
                'calculation_start_period' => '2025-01-01',
                'calculation_end_period' => '2025-12-31',
                'contribution_types' => [$this->pokok->id, $this->wajib->id],
            ]);

        $previewResponse->assertOk()
            ->assertJsonPath('preview.months_count', 12)
            ->assertJsonPath('preview.total_amount', 800000) // 200,000 Pokok + (12 * 50,000 Wajib)
            ->assertJsonPath('preview.has_conflicts', false);

        $this->assertDatabaseCount('cooperative_member_opening_balance_batches', 0);
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);

        // 3. Draft persistence creates batch with lines, but ZERO ledger entries
        $draftResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.members.opening-balance.store', $member), [
                'calculation_start_period' => '2025-01-01',
                'calculation_end_period' => '2025-12-31',
                'contribution_types' => [$this->pokok->id, $this->wajib->id],
                'source_type' => 'MIGRATION_LEDGER',
                'source_reference' => 'MIG-2025-001',
                'notes' => 'Draft migrasi saldo awal anggota',
            ]);

        $draftResponse->assertRedirect();

        $batch = CooperativeMemberOpeningBalanceBatch::query()->first();
        $this->assertNotNull($batch);
        $this->assertSame(OpeningBalanceBatchStatus::Draft, $batch->status);
        $this->assertSame(800000.0, (float) $batch->total_amount);
        $this->assertSame(12, $batch->months_count);
        $this->assertCount(2, $batch->lines);

        // CRITICAL INVARIANT: Zero ledger entries exist while batch is in DRAFT
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);

        // 4. Guards: User without permission (Anggota) gets 403
        $this->actingAs($this->unauthorizedUser)
            ->get(route('cooperative.members.opening-balance.show', $member))
            ->assertForbidden();

        // 5. Guards: Cross-organization access fails closed (404/403)
        $this->actingAs($this->manajerB)
            ->get(route('cooperative.members.opening-balance.show', $member))
            ->assertNotFound();
    }

    // =========================================================================
    // FIN-005: Opening Balance Wizard Post to Ledger
    // =========================================================================

    public function test_fin005_opening_balance_post_creates_ledger_entries_with_idempotency(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);

        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'status' => OpeningBalanceBatchStatus::Draft,
            'months_count' => 12,
            'total_amount' => 800000,
        ]);

        $linePokok = CooperativeMemberOpeningBalanceLine::factory()->pokok(200000)->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->pokok->id,
        ]);

        $lineWajib = CooperativeMemberOpeningBalanceLine::factory()->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->wajib->id,
            'category_snapshot' => 'WAJIB',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'months_count' => 12,
            'unit_amount' => 50000,
            'total_amount' => 600000,
            'calculation_method' => 'MONTHLY',
        ]);

        // Guard 1: Admin Koperasi (lacks approve_cooperative_opening_balance) cannot post
        $this->actingAs($this->adminKoperasiA)
            ->post(route('cooperative.opening-balances.post', $batch), [
                'confirmation_notes' => 'Admin mencoba post',
            ])
            ->assertForbidden();

        // Guard 2: Cross-org Manajer B (without view_cooperative_all and lacking approve_cooperative_opening_balance) cannot post
        $this->actingAs($this->manajerB)
            ->post(route('cooperative.opening-balances.post', $batch), [
                'confirmation_notes' => 'Manajer B mencoba post',
            ])
            ->assertForbidden();

        // 1. Authorized Pengurus A posts the batch
        $response = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.opening-balances.post', $batch), [
                'confirmation_notes' => 'Posting saldo awal migrasi resmi',
            ]);

        $response->assertRedirect();
        $this->assertSame(OpeningBalanceBatchStatus::Posted, $batch->fresh()->status);

        // Exact two ledger entries created with entry_type OPENING_BALANCE
        $this->assertDatabaseCount('cooperative_ledger_entries', 2);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $linePokok->id,
            'entry_type' => 'OPENING_BALANCE',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'POKOK',
            'credit' => '200000.00',
            'debit' => '0.00',
        ]);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $lineWajib->id,
            'entry_type' => 'OPENING_BALANCE',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'credit' => '600000.00',
            'debit' => '0.00',
        ]);

        // 2. IDEMPOTENCY / REPLAY GUARD: Repeated post attempt on already POSTED batch fails closed
        $replayResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.opening-balances.post', $batch), [
                'confirmation_notes' => 'Replay post',
            ]);

        $replayResponse->assertStatus(422);
        // Ledger count remains strictly 2 (no duplicate entries)
        $this->assertDatabaseCount('cooperative_ledger_entries', 2);
    }

    // =========================================================================
    // FIN-006: Opening Balance Wizard Void and Compensating Reversal
    // =========================================================================

    public function test_fin006_opening_balance_void_creates_compensating_reversals_and_restores_balance(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->orgA->id]);

        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'status' => OpeningBalanceBatchStatus::Posted,
            'posted_by' => $this->pengurusA->id,
            'posted_at' => now()->subDays(2),
            'total_amount' => 200000,
        ]);

        $line = CooperativeMemberOpeningBalanceLine::factory()->pokok(200000)->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->pokok->id,
        ]);

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'cooperative_contribution_type_id' => $this->pokok->id,
            'category_snapshot' => 'POKOK',
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $line->id,
            'debit' => 0,
            'credit' => 200000,
            'posted_at' => now()->subDays(2)->toDateString(),
            'description' => 'Saldo awal POKOK',
        ]);

        // Guard 1: User without void permission (Admin Koperasi) gets 403
        $this->actingAs($this->adminKoperasiA)
            ->post(route('cooperative.opening-balances.void', $batch), [
                'reason' => 'Admin mencoba void tanpa izin',
            ])
            ->assertForbidden();

        // Guard 2: Voiding a DRAFT batch fails closed (422)
        $draftBatch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'status' => OpeningBalanceBatchStatus::Draft,
        ]);
        $this->actingAs($this->pengurusA)
            ->post(route('cooperative.opening-balances.void', $draftBatch), [
                'reason' => 'Mencoba void batch draft',
            ])
            ->assertStatus(422);

        // 1. Authorized Pengurus A voids the POSTED batch
        $voidReason = 'Koreksi migrasi saldo awal, data input salah tahun.';
        $response = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.opening-balances.void', $batch), [
                'reason' => $voidReason,
            ]);

        $response->assertRedirect();
        $batch->refresh();
        $this->assertSame(OpeningBalanceBatchStatus::Voided, $batch->status);
        $this->assertSame($voidReason, $batch->void_reason);

        // Compensating reversal entry created (debit = 200,000, credit = 0)
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $line->id,
            'entry_type' => 'OPENING_BALANCE_REVERSAL',
            'category_snapshot' => 'POKOK',
            'debit' => '200000.00',
            'credit' => '0.00',
        ]);

        // Net balance across member's opening balance entries is exactly 0
        $totalCredits = (float) CooperativeLedgerEntry::query()->where('cooperative_member_id', $member->id)->sum('credit');
        $totalDebits = (float) CooperativeLedgerEntry::query()->where('cooperative_member_id', $member->id)->sum('debit');
        $this->assertSame(0.0, $totalCredits - $totalDebits);

        // 2. IDEMPOTENCY / REPLAY GUARD: Repeated void attempt fails closed (422)
        $this->actingAs($this->pengurusA)
            ->post(route('cooperative.opening-balances.void', $batch), [
                'reason' => 'Replay void',
            ])
            ->assertStatus(422);
    }

    // =========================================================================
    // FIN-007: Annual SHU Period Preview, Close, and Revision Workflow
    // =========================================================================

    public function test_fin007_annual_shu_preview_close_and_revision_with_reconciled_allocations(): void
    {
        Carbon::setTestNow('2026-12-15 12:00:00');

        $firstMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'member_no' => 'MBR-SHU-1',
            'joined_at' => '2026-01-01',
        ]);
        $secondMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'member_no' => 'MBR-SHU-2',
            'joined_at' => '2026-06-01',
        ]);

        // Dues payments for members
        foreach (range(1, 12) as $m) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $firstMember->id,
                'cooperative_contribution_type_id' => $this->wajib->id,
                'period' => '2026-'.str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }
        foreach (range(6, 12) as $m) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $secondMember->id,
                'cooperative_contribution_type_id' => $this->wajib->id,
                'period' => '2026-'.str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }

        // 1. GET /cooperative/shu returns preview with calculated allocations
        $response = $this->actingAs($this->pengurusA)->get(route('cooperative.shu.index', [
            'year' => 2026,
            'cooperative_pool' => 300000,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Cooperative/Shu/Index')
            ->has('preview.allocations', 2)
            ->where('preview.cooperative_pool', 300000));

        // 2. POST /cooperative/shu/close closes the period and creates allocation records
        $closeResponse = $this->actingAs($this->pengurusA)->post(route('cooperative.shu.close'), [
            'year' => 2026,
            'cooperative_pool' => 300000,
        ]);

        $closeResponse->assertRedirect();
        $closeResponse->assertSessionHas('success');

        $period = CooperativeShuPeriod::query()->where('year', 2026)->first();
        $this->assertNotNull($period);
        $this->assertSame(CooperativeShuPeriodStatus::Closed, $period->status);
        $this->assertSame(300000.0, (float) $period->cooperative_pool);

        $allocations = CooperativeShuAllocation::query()->where('cooperative_shu_period_id', $period->id)->get();
        $this->assertCount(2, $allocations);

        // RECONCILED CONTRACT DRIFT INVARIANT:
        // SHU close calculates and stores authoritative allocation records,
        // but does NOT automatically post entries into member ledger or bank accounts.
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'entry_type' => 'SHU_DISTRIBUTION',
        ]);

        // 3. IDEMPOTENCY / REPLAY GUARD: Closing the same year again fails closed
        $this->actingAs($this->pengurusA)
            ->post(route('cooperative.shu.close'), [
                'year' => 2026,
                'cooperative_pool' => 300000,
            ])
            ->assertSessionHasErrors(['year']);

        // 4. REVISION WORKFLOW: Request revision reopens the period
        $revisionResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.shu.request-revision', $period), [
                'reason' => 'Perlu penyesuaian laba setelah rapat anggota tahunan.',
            ]);

        $revisionResponse->assertRedirect();
        $period->refresh();
        $this->assertSame(CooperativeShuPeriodStatus::Revision, $period->status);

        // Re-closing transitions status to ClosedRevised
        $recloseResponse = $this->actingAs($this->pengurusA)->post(route('cooperative.shu.close'), [
            'year' => 2026,
            'cooperative_pool' => 350000,
        ]);

        $recloseResponse->assertRedirect();
        $period->refresh();
        $this->assertSame(CooperativeShuPeriodStatus::ClosedRevised, $period->status);
        $this->assertSame(350000.0, (float) $period->cooperative_pool);

        // 5. Unauthorized user cannot close SHU
        $this->actingAs($this->unauthorizedUser)
            ->post(route('cooperative.shu.close'), ['year' => 2027, 'cooperative_pool' => 100000])
            ->assertForbidden();

        Carbon::setTestNow();
    }

    // =========================================================================
    // FIN-008: Member Savings Withdrawal Workflow with Balance Race Protection
    // =========================================================================

    public function test_fin008_savings_withdrawal_full_lifecycle_and_balance_race_protection(): void
    {
        $memberUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $memberUser->id,
        ]);

        // Deposit initial voluntary savings of 250,000
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SIMPANAN_SUKARELA',
            'category_snapshot' => 'SUKARELA',
            'credit' => 250000,
            'debit' => 0,
            'period' => '2026-05',
            'posted_at' => '2026-05-01',
            'description' => 'Setoran awal sukarela',
        ]);

        // 1. Member Request via Sanctum API (POST /api/v1/member/savings/withdraw)
        Sanctum::actingAs($memberUser, ['member:write']);

        // Guard: Amount exceeding voluntary balance rejected (422)
        $this->postJson('/api/v1/member/savings/withdraw', [
            'amount' => 300000,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        // Valid withdrawal request creates PENDING record with ZERO ledger entries
        $reqResponse = $this->postJson('/api/v1/member/savings/withdraw', [
            'amount' => 100000,
            'destination_bank' => 'BCA',
            'destination_account_no' => '1234567890',
            'destination_account_name' => $member->name,
            'reason' => 'Kebutuhan mendesak keluarga',
        ]);

        $reqResponse->assertCreated()
            ->assertJsonPath('data.amount', 100000)
            ->assertJsonPath('data.status', 'PENDING');

        $withdrawalId = $reqResponse->json('data.id');
        $withdrawal = SavingsWithdrawal::query()->findOrFail($withdrawalId);
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);

        // Zero ledger mutations at request stage
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('entry_type', 'SAVING_WITHDRAWAL')->count());

        // 2. Staff Authorization Guard: Admin Koperasi cannot approve (403)
        $this->actingAs($this->adminKoperasiA)
            ->postJson(route('cooperative.savings.withdrawals.process', $withdrawal), [
                'decision' => 'APPROVE',
            ])
            ->assertForbidden();

        // 3. Cross-organization Guard: Manajer B (belonging to Org B without view_cooperative_all) cannot approve Org A withdrawal (403)
        $this->actingAs($this->manajerB)
            ->postJson(route('cooperative.savings.withdrawals.process', $withdrawal), [
                'decision' => 'APPROVE',
            ])
            ->assertForbidden();

        // 4. Staff Approval: Pengurus A approves withdrawal
        $approveResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.savings.withdrawals.process', $withdrawal), [
                'decision' => 'APPROVE',
            ]);

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Processed, $withdrawal->status);
        $this->assertSame($this->pengurusA->id, $withdrawal->approved_by);

        // Exact ONE SAVING_WITHDRAWAL debit entry created
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'source_type' => SavingsWithdrawal::class,
            'source_id' => $withdrawal->id,
            'entry_type' => 'SAVING_WITHDRAWAL',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'debit' => '100000.00',
            'credit' => '0.00',
        ]);

        // Net voluntary balance is now 250,000 - 100,000 = 150,000
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('entry_type', 'SAVING_WITHDRAWAL')->count());

        // 5. BALANCE RACE CONDITION GUARD:
        // Member creates another withdrawal request for 100,000 while balance is 150,000
        Sanctum::actingAs($memberUser, ['member:write']);
        $secondReqResponse = $this->postJson('/api/v1/member/savings/withdraw', [
            'amount' => 100000,
        ]);
        $secondReqResponse->assertCreated();
        $secondWithdrawal = SavingsWithdrawal::query()->findOrFail($secondReqResponse->json('data.id'));

        // Concurrently, an out-of-band withdrawal of 120,000 occurs, reducing balance to 30,000
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->orgA->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_WITHDRAWAL',
            'category_snapshot' => 'SUKARELA',
            'credit' => 0,
            'debit' => 120000,
            'period' => '2026-05',
            'posted_at' => '2026-05-18',
            'description' => 'Penarikan paralel lain',
        ]);

        // Now voluntary balance = 250,000 - 100,000 - 120,000 = 30,000
        // When staff attempts to approve the 100,000 withdrawal, the balance race check MUST fail closed
        $raceResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.savings.withdrawals.process', $secondWithdrawal), [
                'decision' => 'APPROVE',
            ]);

        $raceResponse->assertSessionHasErrors(['amount']);

        // Second withdrawal remains PENDING, and no overdraft debit entry was posted
        $this->assertSame(WithdrawalStatus::Pending, $secondWithdrawal->refresh()->status);
        $this->assertSame(2, CooperativeLedgerEntry::query()->where('entry_type', 'SAVING_WITHDRAWAL')->count());

        // 6. Rejection flow: Staff rejects the second withdrawal
        $rejectResponse = $this->actingAs($this->pengurusA)
            ->post(route('cooperative.savings.withdrawals.process', $secondWithdrawal), [
                'decision' => 'REJECT',
                'rejection_reason' => 'Saldo sukarela tidak mencukupi saat verifikasi.',
            ]);

        $rejectResponse->assertRedirect();
        $this->assertSame(WithdrawalStatus::Rejected, $secondWithdrawal->refresh()->status);
        // Still exact 2 debit entries
        $this->assertSame(2, CooperativeLedgerEntry::query()->where('entry_type', 'SAVING_WITHDRAWAL')->count());
    }
}
