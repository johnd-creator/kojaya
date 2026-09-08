<?php

namespace Tests\Feature\Cooperative;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Sprint2BusinessCriticalFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_request_savings_withdrawal_from_voluntary_balance(): void
    {
        [$user, $member] = $this->memberUser();

        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SIMPANAN_SUKARELA',
            'credit' => 500000,
            'debit' => 0,
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $this->postJson('/api/v1/member/savings/withdraw', [
            'amount' => 150000,
            'destination_bank' => 'BCA',
            'destination_account_no' => '1234567890',
            'destination_account_name' => 'Anggota Test',
            'reason' => 'Kebutuhan mendesak',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonMissingPath('data.cooperative_member_id');
    }

    public function test_member_can_request_loan_restructure_for_owned_loan(): void
    {
        [$user, $member] = $this->memberUser();
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $this->postJson("/api/v1/member/loans/{$loan->id}/restructure", [
            'reason' => 'Pendapatan turun sementara.',
            'proposed_term_months' => 18,
            'proposed_first_due_date' => now()->addMonth()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.loan_id', $loan->id);

        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => \App\Models\LoanRestructure::class,
            'to_status' => 'PENDING',
        ]);
    }

    public function test_npl_aging_report_uses_threshold_and_aging_buckets(): void
    {
        $loanType = LoanType::factory()->create(['npl_threshold_days' => 90]);
        $loan = Loan::factory()->active()->create([
            'loan_type_id' => $loanType->id,
            'outstanding_amount' => 1000000,
            'status' => LoanStatus::Active,
        ]);

        $user = User::factory()->create(['organization_id' => $loan->organization_id]);
        Permission::firstOrCreate(['name' => 'view_cooperative_report']);
        $user->givePermissionTo('view_cooperative_report');
        LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'due_date' => today()->subDays(100)->toDateString(),
            'amount_due' => 250000,
            'amount_paid' => 50000,
            'status' => InstallmentStatus::Overdue,
        ]);

        Sanctum::actingAs($user, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.npl_outstanding', 200000)
            ->assertJsonPath('data.npl_ratio', 0.2)
            ->assertJsonPath('data.buckets.3.bucket', '91-120');
    }

    public function test_pos_return_restores_stock_and_posts_return_ledger(): void
    {
        [$user, $member] = $this->memberUser();
        Permission::firstOrCreate(['name' => 'access_cooperative_pos']);
        $user->givePermissionTo('access_cooperative_pos');

        $product = PosProduct::factory()->create([
            'organization_id' => $member->organization_id,
            'stock' => 10,
            'sale_price' => 20000,
            'cost_price' => 15000,
        ]);

        Sanctum::actingAs($user, ['pos:write']);

        $transactionId = $this->postJson('/api/v1/pos/transactions', [
            'cooperative_member_id' => $member->id,
            'payment_method' => 'CASH',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated()->json('data.id');

        $itemId = \App\Models\PosTransactionItem::query()
            ->where('pos_transaction_id', $transactionId)
            ->value('id');

        $this->postJson('/api/v1/pos/returns', [
            'pos_transaction_id' => $transactionId,
            'reason' => 'Barang rusak',
            'items' => [
                ['pos_transaction_item_id' => $itemId, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', '20000.00');

        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'stock' => 9,
        ]);
        $this->assertDatabaseHas('pos_stock_movements', [
            'pos_product_id' => $product->id,
            'movement_type' => 'RETURN',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'POS_RETURN',
            'credit' => 20000,
        ]);
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function memberUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return [$user, $member];
    }
}
