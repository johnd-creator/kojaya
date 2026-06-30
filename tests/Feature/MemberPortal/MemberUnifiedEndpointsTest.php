<?php

namespace Tests\Feature\MemberPortal;

use App\Enums\InstallmentStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\MemberPaymentIntent;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberUnifiedEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => '']);
    }

    public function test_profile_exposes_extended_personal_and_bank_fields(): void
    {
        [$user, $member] = $this->memberUser();
        $member->forceFill([
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-05-10',
            'tempat_lahir' => 'Bandung',
            'pekerjaan' => 'Guru',
            'npwp' => '12.345.678.9-012.000',
            'nama_bank' => 'BRI',
            'no_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Budi Santoso',
        ])->save();

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/profile')
            ->assertOk()
            ->assertJsonPath('data.member.gender', 'L')
            ->assertJsonPath('data.member.birth_date', '1990-05-10')
            ->assertJsonPath('data.member.birth_place', 'Bandung')
            ->assertJsonPath('data.member.occupation', 'Guru')
            ->assertJsonPath('data.member.npwp', '12.345.678.9-012.000')
            ->assertJsonPath('data.member.bank_name', 'BRI')
            ->assertJsonPath('data.member.bank_account_number', '1234567890')
            ->assertJsonPath('data.member.bank_account_holder', 'Budi Santoso');
    }

    public function test_profile_update_persists_extended_fields(): void
    {
        [$user, $member] = $this->memberUser();

        Sanctum::actingAs($user, ['member:write']);

        $this->putJson('/api/v1/member/profile', [
            'name' => 'Budi Santoso',
            'email' => $member->email,
            'gender' => 'L',
            'birth_date' => '1991-01-15',
            'birth_place' => 'Surabaya',
            'occupation' => 'Wiraswasta',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '9876543210',
            'bank_account_holder' => 'Budi S',
        ])->assertOk()
            ->assertJsonPath('data.member.bank_name', 'Mandiri')
            ->assertJsonPath('data.member.bank_account_number', '9876543210');

        $this->assertDatabaseHas('cooperative_members', [
            'id' => $member->id,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Surabaya',
            'nama_bank' => 'Mandiri',
            'no_rekening' => '9876543210',
        ]);
    }

    public function test_profile_update_rejects_invalid_gender_and_future_birth_date(): void
    {
        [$user] = $this->memberUser();

        Sanctum::actingAs($user, ['member:write']);

        $this->putJson('/api/v1/member/profile', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'gender' => 'X',
        ])->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $this->putJson('/api/v1/member/profile', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'birth_date' => now()->addCentury()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_payment_show_returns_own_payment_and_blocks_others(): void
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
        $ownInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        $ownPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $ownInvoice->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'QRIS',
            'gateway_status' => 'PENDING',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        $otherPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'cooperative_dues_invoice_id' => null,
            'amount' => 25000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/payments/'.$ownPayment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $ownPayment->id)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.gateway_status', 'PENDING');

        $this->getJson('/api/v1/member/payments/'.$otherPayment->id)
            ->assertForbidden();
    }

    public function test_unified_bills_merge_dues_and_loan_installments_with_summary(): void
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
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 20000,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'PARTIAL',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->subMonth()->format('Y-m'),
            'amount' => 50000,
            'paid_amount' => 50000,
            'due_date' => now()->subWeek()->toDateString(),
            'status' => 'PAID',
        ]);

        $loanType = LoanType::factory()->create(['name' => 'Pinjaman Produktif']);
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
        ]);
        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 0,
            'paid_at' => null,
            'status' => InstallmentStatus::Pending->value,
        ]);
        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 2,
            'due_date' => now()->subWeek()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 460000,
            'paid_at' => now()->subWeek()->toDateString(),
            'status' => InstallmentStatus::Paid->value,
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $response = $this->getJson('/api/v1/member/bills')
            ->assertOk()
            ->assertJsonPath('summary.dues_count', 1)
            ->assertJsonPath('summary.loan_count', 1)
            ->assertJsonPath('summary.payable_count', 2)
            ->assertJsonPath('summary.total_remaining', 540000);

        // PAID dues invoice and PAID loan installment are excluded.
        $this->assertCount(2, $response->json('data'));
        $sources = collect($response->json('data'))->pluck('source')->sort()->values()->all();
        $this->assertSame(['dues', 'loan'], $sources);

        // Category filter scopes to a single source.
        $this->getJson('/api/v1/member/bills?category=loan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'loan')
            ->assertJsonPath('data.0.title', 'Pinjaman Produktif · Angsuran #1');
    }

    public function test_unified_bills_detail_resolves_composite_ids(): void
    {
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
        $loanType = LoanType::factory()->create(['name' => 'Pinjaman Dana']);
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
        ]);
        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 0,
            'paid_at' => null,
            'status' => InstallmentStatus::Pending->value,
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/bills/dues:'.$invoice->id)
            ->assertOk()
            ->assertJsonPath('data.source', 'dues')
            ->assertJsonPath('data.source_id', (string) $invoice->id)
            ->assertJsonPath('data.title', 'Simpanan Pokok')
            ->assertJsonPath('data.remaining_amount', 500000);

        $this->getJson('/api/v1/member/bills/loan:'.$installment->id)
            ->assertOk()
            ->assertJsonPath('data.source', 'loan')
            ->assertJsonPath('data.source_id', (string) $installment->id)
            ->assertJsonPath('data.remaining_amount', 460000);

        $this->getJson('/api/v1/member/bills/unknown:1')->assertNotFound();
    }

    public function test_unified_bills_detail_blocks_other_member_loan_installment(): void
    {
        [$user] = $this->memberUser();
        $otherMember = CooperativeMember::factory()->active()->create();
        $loanType = LoanType::factory()->create();
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $otherMember->id,
            'loan_type_id' => $loanType->id,
        ]);
        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 0,
            'paid_at' => null,
            'status' => InstallmentStatus::Pending->value,
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/bills/loan:'.$installment->id)->assertNotFound();
    }

    public function test_unified_bills_include_pos_credit_outstanding_balance(): void
    {
        [$user, $member] = $this->memberUser();
        $member->forceFill([
            'credit_limit' => 500000,
            'outstanding_balance' => 125000,
        ])->save();

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/bills?category=pos_credit')
            ->assertOk()
            ->assertJsonPath('summary.pos_credit_count', 1)
            ->assertJsonPath('summary.total_remaining', 125000)
            ->assertJsonPath('data.0.id', 'pos_credit:'.$member->id)
            ->assertJsonPath('data.0.source', 'pos_credit')
            ->assertJsonPath('data.0.title', 'Kredit Belanja POS');
    }

    public function test_member_can_create_payment_intent_for_dues_bill(): void
    {
        [$user, $member] = $this->memberUser();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-INTENT',
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
            'paid_amount' => 20000,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'PARTIAL',
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/bills/dues:'.$invoice->id.'/payment-intent', [
            'channel' => 'QRIS',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bill_id', 'dues:'.$invoice->id)
            ->assertJsonPath('data.source', 'dues')
            ->assertJsonPath('data.payment.amount', 80000)
            ->assertJsonPath('data.payment.gateway_provider', 'internal')
            ->assertJsonPath('data.payment.gateway_status', 'PENDING')
            ->assertJsonPath('data.charge.provider', 'internal')
            ->assertJsonPath('data.charge.channel', 'QRIS');

        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 80000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'internal',
            'gateway_status' => 'PENDING',
            'status' => 'PENDING',
        ]);

        $retry = $this->postJson('/api/v1/member/bills/dues:'.$invoice->id.'/payment-intent', [
            'channel' => 'QRIS',
        ])
            ->assertCreated()
            ->assertJsonPath('data.charge.reference', $response->json('data.charge.reference'));

        $this->assertSame($response->json('data.payment.id'), $retry->json('data.payment.id'));
        $this->assertSame(1, CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoice->id)->count());
    }

    public function test_member_can_create_and_settle_payment_intent_for_loan_bill(): void
    {
        [$user, $member] = $this->memberUser();
        $loanType = LoanType::factory()->create(['name' => 'Pinjaman Produktif']);
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
        ]);
        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 0,
            'paid_at' => null,
            'status' => InstallmentStatus::Pending->value,
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/bills/loan:'.$installment->id.'/payment-intent', [
            'channel' => 'QRIS',
        ])->assertCreated()
            ->assertJsonPath('data.source', 'loan')
            ->assertJsonPath('data.payment_intent.payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
            ->assertJsonPath('data.payment_intent.amount', 460000)
            ->assertJsonPath('data.charge.provider', 'internal');

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID');

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'cooperative_member_id' => $member->id,
            'amount' => 460000,
            'payment_method' => 'QRIS',
            'reference_no' => $response->json('data.charge.reference'),
        ]);

        $this->assertSame(1, LoanPayment::query()->count());
        $this->assertNotNull(MemberPaymentIntent::query()->firstOrFail()->settled_at);
    }

    public function test_unified_transactions_merge_pos_and_payments_timeline(): void
    {
        [$user, $member] = $this->memberUser();
        $product = PosProduct::factory()->create();
        $posTransaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-20260628-001',
            'cooperative_member_id' => $member->id,
            'cashier_id' => $user->id,
            'subtotal' => 75000,
            'discount_amount' => 0,
            'total_amount' => 75000,
            'status' => 'COMPLETED',
            'sold_at' => now()->subHour(),
        ]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $posTransaction->id,
            'pos_product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 75000,
            'line_total' => 75000,
        ]);
        PosPayment::query()->create([
            'pos_transaction_id' => $posTransaction->id,
            'payment_method' => 'CASH',
            'amount' => 75000,
        ]);

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
        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/transactions/unified')
            ->assertOk()
            ->assertJsonPath('summary.pos_count', 1)
            ->assertJsonPath('summary.payment_count', 1)
            ->assertJsonPath('summary.total_count', 2)
            ->assertJsonPath('summary.total_amount', 175000)
            ->assertJsonPath('meta.total', 2);

        $sources = $this->getJson('/api/v1/member/transactions/unified?source=pos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0.source');
        $this->assertSame('pos', $sources);
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
