<?php

namespace Tests\Feature\Cooperative;

use App\Enums\LoanStatus;
use App\Models\ChartOfAccount;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativePeriodLock;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\Cooperative\AnnualShuDistributionService;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\LoanService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint2BusinessHardeningTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
    }

    public function test_member_resignation_is_blocked_when_obligations_are_open(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('System Admin');
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->organization->id]);

        Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->organization->id,
            'outstanding_amount' => 500000,
        ]);
        RewardRedemption::factory()->create([
            'cooperative_member_id' => $member->id,
            'status' => 'PROCESSING',
        ]);

        $this->actingAs($admin)
            ->postJson(route('cooperative.members.resign', $member))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('member')
            ->assertJsonPath('errors.member.0', 'Anggota masih memiliki pinjaman berjalan atau saldo pinjaman belum lunas.');

        $this->assertSame('ACTIVE', $member->refresh()->status);
    }

    public function test_member_cannot_apply_second_loan_with_outstanding_loan(): void
    {
        [$user, $member] = $this->memberUser();
        $loanType = LoanType::factory()->create(['is_active' => true]);
        Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->organization->id,
            'loan_type_id' => $loanType->id,
            'outstanding_amount' => 1000000,
        ]);

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->postJson('/api/v1/member/loans', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'purpose' => 'Kebutuhan keluarga',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('loan')
            ->assertJsonPath('errors.loan.0', 'Anggota masih memiliki pinjaman berjalan.');
    }

    public function test_period_lock_blocks_payment_loan_journal_and_shu_posting(): void
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $this->organization->id]);
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $payment = $this->pendingPayment($member, $admin, '2026-05');

        $this->lockPeriod('2026-05', 'COOPERATIVE');

        $this->assertLocked(fn () => app(CooperativePaymentService::class)->approve($payment, $admin));

        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->organization->id,
            'status' => LoanStatus::Active,
            'outstanding_amount' => 1200000,
        ]);
        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => '2026-05-20',
            'principal_amount' => 1000000,
            'interest_amount' => 200000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 1200000,
            'amount_paid' => 0,
            'status' => 'PENDING',
        ]);

        $this->assertLocked(fn () => app(LoanService::class)->recordPayment($loan, [
            'amount' => 250000,
            'paid_at' => '2026-05-21',
            'payment_method' => 'TRANSFER',
        ], $admin));

        $this->lockPeriod('2026-05', 'FINANCE');
        $debitAccount = $this->account('1100', 'Kas', 'DEBIT');
        $creditAccount = $this->account('4100', 'Pendapatan', 'CREDIT');

        $this->assertLocked(fn () => app(JournalEntryService::class)->create([
            'organization_id' => $this->organization->id,
            'entry_date' => '2026-05-31',
            'description' => 'Jurnal periode terkunci',
            'lines' => [
                ['chart_of_account_id' => $debitAccount->id, 'debit' => 100000, 'credit' => 0],
                ['chart_of_account_id' => $creditAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
        ], $admin));

        $this->lockPeriod('2026-12', 'COOPERATIVE');

        $this->assertLocked(fn () => app(AnnualShuDistributionService::class)->close(2026, 1000000, 0, $admin));
    }

    public function test_approved_dues_payment_issues_receipt_pdf_and_signed_download_url(): void
    {
        Storage::fake('local');

        [$memberUser, $member] = $this->memberUser();
        $approver = User::factory()->create(['organization_id' => $this->organization->id]);
        $payment = $this->pendingPayment($member, $memberUser, '2026-06');

        $approvedPayment = app(CooperativePaymentService::class)->approve($payment, $approver);
        $receipt = $approvedPayment->receipt;

        $this->assertNotNull($receipt);
        $this->assertSame($receipt->receipt_no, $approvedPayment->receipt_no);
        Storage::disk('local')->assertExists($receipt->pdf_path);

        Sanctum::actingAs($memberUser, ['member:read']);

        $downloadUrl = $this->getJson("/api/v1/member/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.receipt_no', $receipt->receipt_no)
            ->json('data.download_url');

        $this->actingAs($memberUser)
            ->get($downloadUrl)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function memberUser(): array
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return [$user, $member];
    }

    private function contributionType(): CooperativeContributionType
    {
        return CooperativeContributionType::query()->create([
            'code' => 'WAJIB-'.fake()->unique()->numerify('###'),
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
    }

    private function pendingPayment(CooperativeMember $member, User $payer, string $period): CooperativePayment
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $this->contributionType()->id,
            'period' => $period,
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => $period.'-10',
            'status' => 'UNPAID',
        ]);

        return CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $payer->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => $period.'-12',
            'status' => 'PENDING',
        ]);
    }

    private function lockPeriod(string $period, string $module): void
    {
        CooperativePeriodLock::query()->updateOrCreate(
            ['period' => $period, 'module' => $module],
            ['status' => 'LOCKED', 'locked_at' => now()]
        );
    }

    private function account(string $code, string $name, string $normalBalance): ChartOfAccount
    {
        return ChartOfAccount::query()->create([
            'organization_id' => $this->organization->id,
            'code' => $code,
            'name' => $name,
            'account_type' => $normalBalance === 'DEBIT' ? 'ASSET' : 'REVENUE',
            'normal_balance' => $normalBalance,
            'category' => 'OPERASIONAL',
            'is_active' => true,
        ]);
    }

    private function assertLocked(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a period lock validation exception.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Periode telah dikunci', implode(' ', array_merge(...array_values($exception->errors()))));
        }
    }
}
