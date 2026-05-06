<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuAllocation;
use App\Models\CooperativeShuPeriod;
use App\Models\Loan;
use App\Models\LoanType;
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
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVINGS_DEPOSIT',
            'credit' => 250000,
            'debit' => 0,
            'posted_at' => now()->subDay()->toDateString(),
        ]);
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'LOAN_DISBURSEMENT',
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
            ->assertJsonPath('data.total_balance', 200000)
            ->assertJsonPath('data.pending_invoices', 1);

        $this->getJson('/api/v1/member/savings/ledger')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.running_balance', 200000);

        $this->getJson('/api/v1/member/dues/invoices')
            ->assertOk()
            ->assertJsonPath('data.0.id', $invoice->id);

        $this->getJson('/api/v1/member/payments')
            ->assertOk()
            ->assertJsonPath('data.0.cooperative_member_id', $member->id);
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
            ->assertJsonPath('data.cooperative_member_id', $member->id);

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
            ->assertJsonPath('data.cooperative_member_id', $member->id)
            ->json('data.id');

        $this->getJson('/api/v1/member/loans')
            ->assertOk()
            ->assertJsonPath('data.0.id', $loanId);

        $this->getJson('/api/v1/member/loans/'.$loanId)
            ->assertOk()
            ->assertJsonPath('data.id', $loanId);

        $this->getJson('/api/v1/member/loans/'.$otherLoan->id)
            ->assertForbidden();
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
            ->assertJsonPath('data.cooperative_member_id', $member->id)
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
