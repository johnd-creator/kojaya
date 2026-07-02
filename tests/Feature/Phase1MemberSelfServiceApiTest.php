<?php

namespace Tests\Feature;

use App\Models\ApprovalLog;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuAllocation;
use App\Models\CooperativeShuPeriod;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase1MemberSelfServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_savings_invoices_and_payments_endpoints_are_scoped_to_member(): void
    {
        [$user, $member] = $this->memberUser();
        $otherMember = CooperativeMember::factory()->active()->create();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->subMonth()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        $posType = CooperativeContributionType::query()->create([
            'code' => 'POS-CREDIT',
            'name' => 'Tagihan Belanja POS',
            'category' => 'POS',
            'default_amount' => 125000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $posInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $posType->id,
            'period' => now()->format('Y-m'),
            'amount' => 125000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVINGS_DEPOSIT',
            'cooperative_contribution_type_id' => $type->id,
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'credit' => 250000,
            'debit' => 0,
            'posted_at' => now()->subDay()->toDateString(),
        ]);
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'LOAN_DISBURSEMENT',
            'ledger_scope' => 'LOAN',
            'credit' => 0,
            'debit' => 50000,
            'posted_at' => now()->toDateString(),
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/savings/summary')
            ->assertOk()
            ->assertJsonPath('data.total_balance', 250000)
            ->assertJsonPath('data.by_category.WAJIB', 250000)
            ->assertJsonPath('data.pending_invoices', 1);

        $this->getJson('/api/v1/member/savings/ledger')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'WAJIB')
            ->assertJsonPath('data.0.balance_delta', 250000);

        $this->getJson('/api/v1/member/dues/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonMissing(['id' => $posInvoice->id]);

        $this->getJson('/api/v1/member/payments')
            ->assertOk()
            ->assertJsonPath('data.0.amount', 50000)
            ->assertJsonMissingPath('data.0.cooperative_member_id');
    }

    public function test_member_dashboard_and_invoice_list_ignore_deleted_invoices(): void
    {
        [$user, $member] = $this->memberUser();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $visibleInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        $deletedInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->subMonth()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        $deletedInvoice->delete();

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.pending_invoices', 1);

        $this->getJson('/api/v1/member/dues/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleInvoice->id)
            ->assertJsonMissing(['id' => $deletedInvoice->id]);
    }

    public function test_member_can_upload_payment_proof_for_own_invoice(): void
    {
        Storage::fake('public');
        [$user, $member] = $this->memberUser();
        $type = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 500000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 500000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 500000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'reference_no' => 'TRX-001',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertCreated()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.invoice_id', $invoice->id)
            ->assertJsonMissingPath('data.cooperative_member_id');

        Storage::disk('public')->assertExists($response->json('data.proof_path'));
    }

    public function test_member_loan_endpoints_apply_and_enforce_ownership(): void
    {
        [$user, $member] = $this->memberUser();
        $loanType = LoanType::factory()->create(['is_active' => true]);
        $otherLoan = Loan::factory()->create();

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $loanId = $this->postJson('/api/v1/member/loans', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'purpose' => 'Modal usaha',
        ])->assertCreated()
            ->assertJsonPath('data.member_id', $member->id)
            ->json('data.id');

        ApprovalLog::query()->create([
            'subject_type' => Loan::class,
            'subject_id' => (string) $loanId,
            'from_status' => null,
            'to_status' => 'APPLIED',
            'approved_by' => $user->id,
            'note' => 'Pengajuan dari aplikasi member.',
        ]);

        $this->getJson('/api/v1/member/loans')
            ->assertOk()
            ->assertJsonPath('data.0.id', $loanId);

        $this->getJson('/api/v1/member/loans/'.$loanId)
            ->assertOk()
            ->assertJsonPath('data.id', $loanId)
            ->assertJsonPath('data.approval_logs.0.to_status', 'APPLIED');

        $this->getJson('/api/v1/member/loans/'.$otherLoan->id)
            ->assertForbidden();
    }

    public function test_member_loan_application_is_idempotent_with_matching_key(): void
    {
        [$user, $member] = $this->memberUser();
        $loanType = LoanType::factory()->create(['is_active' => true]);
        $payload = [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'purpose' => 'Modal usaha',
        ];

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $firstLoanId = $this->postJson('/api/v1/member/loans', $payload, [
            'Idempotency-Key' => 'loan-apply-key-001',
        ])->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/member/loans', $payload, [
            'Idempotency-Key' => 'loan-apply-key-001',
        ])->assertCreated()
            ->assertHeader('X-Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $firstLoanId);

        $this->assertSame(1, Loan::query()
            ->where('cooperative_member_id', $member->id)
            ->where('loan_type_id', $loanType->id)
            ->count());
    }

    public function test_idempotency_key_rejects_different_payload_reuse(): void
    {
        [$user] = $this->memberUser();
        $loanType = LoanType::factory()->create(['is_active' => true]);

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->postJson('/api/v1/member/loans', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
        ], [
            'Idempotency-Key' => 'loan-apply-key-002',
        ])->assertCreated();

        $this->postJson('/api/v1/member/loans', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 2000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
        ], [
            'Idempotency-Key' => 'loan-apply-key-002',
        ])->assertConflict()
            ->assertJsonPath('error_code', 'CONFLICT');
    }

    public function test_member_shu_notifications_and_support_ticket_endpoints(): void
    {
        [$user, $member] = $this->memberUser();
        $period = CooperativeShuPeriod::query()->create([
            'year' => now()->year - 1,
            'cooperative_pool' => 10000000,
            'pos_profit_pool' => 5000000,
            'total_membership_score' => 10,
            'total_dues_score' => 10,
            'total_shu_score' => 20,
            'total_pos_points' => 100,
            'status' => 'CLOSED',
            'closed_at' => now(),
            'closed_by' => $user->id,
        ]);
        CooperativeShuAllocation::query()->create([
            'cooperative_shu_period_id' => $period->id,
            'cooperative_member_id' => $member->id,
            'membership_score' => 10,
            'dues_score' => 10,
            'shu_score' => 20,
            'cooperative_shu_amount' => 250000,
            'pos_points' => 100,
            'pos_shu_amount' => 100000,
            'total_amount' => 350000,
        ]);
        $user->notify(new class extends \Illuminate\Notifications\Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['message' => 'Tagihan baru tersedia'];
            }
        });

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/shu')
            ->assertOk()
            ->assertJsonPath('data.0.allocations.0.total_amount', '350000.00');

        $this->getJson('/api/v1/member/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.data.message', 'Tagihan baru tersedia');

        $this->postJson('/api/v1/member/support-tickets', [
            'category' => 'PAYMENT',
            'priority' => 'HIGH',
            'subject' => 'Pembayaran belum diverifikasi',
            'message' => 'Saya sudah upload bukti transfer.',
        ])->assertCreated()
            ->assertJsonPath('data.category', 'PAYMENT')
            ->assertJsonPath('data.status', 'OPEN');

        $this->assertDatabaseHas('cooperative_support_tickets', [
            'cooperative_member_id' => $member->id,
            'category' => 'PAYMENT',
            'status' => 'OPEN',
        ]);

        $this->getJson('/api/v1/member/support-tickets')
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Pembayaran belum diverifikasi');
    }

    public function test_member_transactions_endpoint_returns_own_pos_history_and_summary(): void
    {
        [$user, $member] = $this->memberUser();
        $otherMember = CooperativeMember::factory()->active()->create();
        $product = PosProduct::factory()->create([
            'name' => 'Beras Koperasi',
            'sku' => 'BR-KOP-001',
        ]);
        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-20260610-001',
            'cooperative_member_id' => $member->id,
            'cashier_id' => $user->id,
            'subtotal' => 300000,
            'discount_amount' => 0,
            'total_amount' => 300000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $transaction->id,
            'pos_product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 150000,
            'line_total' => 300000,
        ]);
        PosPayment::query()->create([
            'pos_transaction_id' => $transaction->id,
            'payment_method' => 'CASH',
            'amount' => 300000,
        ]);
        PosTransaction::query()->create([
            'transaction_no' => 'POS-20260610-OTHER',
            'cooperative_member_id' => $otherMember->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/transactions')
            ->assertOk()
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('summary.total_amount', 300000)
            ->assertJsonPath('summary.total_items', 2)
            ->assertJsonPath('transactions.data.0.transaction_no', 'POS-20260610-001')
            ->assertJsonPath('transactions.data.0.items.0.product.name', 'Beras Koperasi')
            ->assertJsonPath('transactions.data.0.payments.0.payment_method', 'CASH')
            ->assertJsonMissingPath('transactions.data.1');
    }

    public function test_member_reward_redemptions_endpoint_returns_own_redemptions(): void
    {
        [$user, $member] = $this->memberUser();
        $otherMember = CooperativeMember::factory()->active()->create();
        $reward = Reward::factory()->create([
            'name' => 'Voucher Belanja',
            'points_required' => 500,
        ]);
        RewardRedemption::factory()->create([
            'reward_id' => $reward->id,
            'cooperative_member_id' => $member->id,
            'quantity' => 2,
            'points_used' => 1000,
            'status' => 'PENDING',
            'redeemed_at' => now(),
        ]);
        RewardRedemption::factory()->create([
            'reward_id' => $reward->id,
            'cooperative_member_id' => $otherMember->id,
            'status' => 'PENDING',
            'redeemed_at' => now(),
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/reward-redemptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reward.name', 'Voucher Belanja')
            ->assertJsonPath('data.0.quantity', 2)
            ->assertJsonPath('data.0.points_used', 1000);
    }

    public function test_member_can_retrieve_points_balance_and_history(): void
    {
        [$user, $member] = $this->memberUser();

        \App\Models\PointTransaction::query()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 1200,
            'balance_before' => 0,
            'balance_after' => 1200,
            'description' => 'Awal',
            'posted_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/points/balance')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_points', 1200);

        $this->getJson('/api/v1/points/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.points', 1200);
    }

    public function test_member_can_retrieve_rewards_and_redeem(): void
    {
        [$user, $member] = $this->memberUser();

        \App\Models\PointTransaction::query()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 1500,
            'balance_before' => 0,
            'balance_after' => 1500,
            'description' => 'Awal',
            'posted_at' => now()->toDateString(),
        ]);

        $reward = Reward::query()->create([
            'organization_id' => $member->organization_id,
            'name' => 'Voucher Keren',
            'category' => 'DISKON',
            'description' => 'Voucher keren',
            'points_required' => 500,
            'stock' => 10,
            'valid_until' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/rewards')
            ->assertOk()
            ->assertJsonPath('data.0.id', $reward->id);

        $this->postJson('/api/v1/rewards/'.$reward->id.'/redeem', [
            'quantity' => 1,
            'delivery_address' => 'Jl. Jalan 123',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reward.id', $reward->id);
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\CooperativeMember}
     */
    private function memberUser(): array
    {
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return [$user, $member];
    }
}
