<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SavingsWithdrawal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsWithdrawalApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pengurus_can_approve_withdrawal_and_member_is_notified(): void
    {
        [$organization, $memberUser, $member] = $this->memberWithVoluntaryBalance(300000);
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'amount' => 100000,
            'status' => \App\Enums\WithdrawalStatus::Pending,
            'destination_bank' => 'BRI',
            'destination_account_no' => '123456',
            'destination_account_name' => $member->name,
        ]);

        $response = $this->actingAs($pengurus)
            ->postJson("/cooperative/savings/withdrawals/{$withdrawal->id}/process", [
                'decision' => 'APPROVE',
            ]);

        $response->assertSessionHas('success');
        $this->assertSame(\App\Enums\WithdrawalStatus::Processed, $withdrawal->fresh()->status);
        $this->assertTrue(
            $memberUser->notifications()->where('data->event_type', 'member.withdrawal.approved')->exists()
        );
    }

    public function test_pengurus_can_reject_withdrawal_and_member_is_notified(): void
    {
        [$organization, $memberUser, $member] = $this->memberWithVoluntaryBalance(300000);
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'amount' => 100000,
            'status' => \App\Enums\WithdrawalStatus::Pending,
        ]);

        $this->actingAs($pengurus)
            ->postJson("/cooperative/savings/withdrawals/{$withdrawal->id}/process", [
                'decision' => 'REJECT',
                'rejection_reason' => 'Saldo tidak mencukupi setelah verifikasi.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(\App\Enums\WithdrawalStatus::Rejected, $withdrawal->fresh()->status);
        $this->assertTrue(
            $memberUser->notifications()->where('data->event_type', 'member.withdrawal.rejected')->exists()
        );
    }

    public function test_admin_koperasi_can_view_but_cannot_process_withdrawal(): void
    {
        [$organization, $memberUser, $member] = $this->memberWithVoluntaryBalance(300000);
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'amount' => 100000,
            'status' => \App\Enums\WithdrawalStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->get('/cooperative/savings/withdrawals')
            ->assertOk();

        $this->actingAs($admin)
            ->postJson("/cooperative/savings/withdrawals/{$withdrawal->id}/process", [
                'decision' => 'APPROVE',
            ])
            ->assertForbidden();
    }

    /**
     * @return array{0: Organization, 1: User, 2: CooperativeMember}
     */
    private function memberWithVoluntaryBalance(float $amount): array
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);

        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SIMPANAN_SUKARELA',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'credit' => $amount,
            'debit' => 0,
        ]);

        return [$organization, $memberUser, $member];
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
