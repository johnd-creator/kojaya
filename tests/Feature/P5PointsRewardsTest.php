<?php

namespace Tests\Feature;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class P5PointsRewardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_portal_pages_render_with_points_and_rewards_data(): void
    {
        [$user, $member, $organization] = $this->createMemberUser();

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_PAYMENT',
            'debit' => 0,
            'credit' => 750000,
            'period' => now()->format('Y-m'),
            'description' => 'Simpanan wajib',
            'posted_at' => now()->toDateString(),
        ]);

        PointTransaction::query()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 1800,
            'balance_before' => 0,
            'balance_after' => 1800,
            'description' => 'Poin belanja koperasi',
            'posted_at' => now()->toDateString(),
        ]);

        Reward::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Voucher Belanja',
            'category' => 'Voucher',
            'description' => 'Voucher koperasi Rp50.000',
            'points_required' => 500,
            'stock' => 20,
            'valid_until' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        LoanType::factory()->create();

        $this->actingAs($user)
            ->get('/member')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Dashboard')
                ->where('summary.points_balance', 1800)
            );

        $this->actingAs($user)
            ->get('/member/points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Points')
                ->where('summary.total_points', 1800)
                ->has('history.data', 1)
            );

        $this->actingAs($user)
            ->get('/member/rewards')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Rewards')
                ->has('rewards.data', 1)
            );

        $this->actingAs($user)
            ->get('/member/savings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Savings')
                ->where('summary.savings_balance', 750000)
            );
    }

    public function test_member_can_apply_for_loan_redeem_reward_and_update_profile(): void
    {
        [$user, $member, $organization] = $this->createMemberUser();

        PointTransaction::query()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 2000,
            'balance_before' => 0,
            'balance_after' => 2000,
            'description' => 'Saldo awal poin',
            'posted_at' => now()->toDateString(),
        ]);

        $loanType = LoanType::factory()->create();
        $reward = Reward::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Merchandise Eksklusif',
            'category' => 'Merchandise',
            'description' => 'Hadiah loyalitas anggota',
            'points_required' => 600,
            'stock' => 10,
            'valid_until' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/member/loans', [
                'loan_type_id' => $loanType->id,
                'principal_amount' => 2500000,
                'term_months' => 6,
                'first_due_date' => now()->addMonth()->toDateString(),
                'purpose' => 'Kebutuhan sekolah',
                'notes' => 'Diajukan dari portal anggota',
            ])
            ->assertRedirect('/member/loans');

        $this->assertDatabaseHas('loans', [
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->post('/member/rewards/'.$reward->id.'/redeem', [
                'quantity' => 1,
                'delivery_address' => 'Jl. Anggota No. 10',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reward_redemptions', [
            'cooperative_member_id' => $member->id,
            'reward_id' => $reward->id,
            'points_used' => 600,
        ]);

        $this->actingAs($user)
            ->put('/member/profile', [
                'name' => 'Anggota Portal',
                'email' => 'anggota-portal@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Portal Anggota',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Anggota Portal',
            'email' => 'anggota-portal@example.com',
        ]);

        $this->assertDatabaseHas('cooperative_members', [
            'id' => $member->id,
            'name' => 'Anggota Portal',
            'email' => 'anggota-portal@example.com',
            'phone' => '08123456789',
        ]);

        $this->assertSame(1, Loan::query()->where('cooperative_member_id', $member->id)->count());
    }

    private function createMemberUser(): array
    {
        Role::query()->firstOrCreate(['name' => 'Anggota', 'guard_name' => 'web']);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [$user, $member, $organization];
    }
}
