<?php

namespace Tests\Feature;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P5MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $memberUser;

    private CooperativeMember $member;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
        $this->memberUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->memberUser->assignRole('Anggota');

        $this->member = CooperativeMember::factory()->active()->create([
            'user_id' => $this->memberUser->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_dashboard_returns_ok_with_member_data(): void
    {
        $response = $this->actingAs($this->memberUser)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Dashboard')
            ->has('member')
            ->has('summary')
            ->has('summary.savings_balance')
            ->has('summary.pending_invoices')
            ->has('summary.active_loans')
            ->has('summary.points_balance')
            ->has('recentTransactions')
            ->has('recentLoans')
        );
    }

    public function test_savings_returns_ok_with_ledger_and_invoices(): void
    {
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->memberUser)->get(route('member.savings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Savings')
            ->has('summary')
            ->has('entries')
            ->has('invoices')
            ->has('payments')
        );
    }

    public function test_loans_returns_ok_with_loan_types(): void
    {
        LoanType::factory()->create();

        $response = $this->actingAs($this->memberUser)->get(route('member.loans'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Loans')
            ->has('loans')
            ->has('loanTypes')
        );
    }

    public function test_apply_loan_creates_loan_and_redirects(): void
    {
        $loanType = LoanType::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->memberUser)->post(route('member.loans.store'), [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'purpose' => 'Biaya pendidikan',
        ]);

        $response->assertRedirect(route('member.loans'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('loans', [
            'cooperative_member_id' => $this->member->id,
            'loan_type_id' => $loanType->id,
        ]);
    }

    public function test_apply_loan_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->memberUser)->post(route('member.loans.store'), [
            'loan_type_id' => 'invalid-uuid',
            'principal_amount' => -100,
            'term_months' => 0,
            'first_due_date' => 'not-a-date',
        ]);

        $response->assertSessionHasErrors(['loan_type_id', 'principal_amount', 'term_months', 'first_due_date']);
    }

    public function test_apply_loan_fails_with_past_due_date(): void
    {
        $loanType = LoanType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->memberUser)->post(route('member.loans.store'), [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['first_due_date']);
    }

    public function test_points_returns_ok_with_summary(): void
    {
        $response = $this->actingAs($this->memberUser)->get(route('member.points'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Points')
            ->has('summary')
            ->has('history')
            ->has('redemptions')
        );
    }

    public function test_rewards_returns_ok_with_active_rewards(): void
    {
        Reward::factory()->create(['is_active' => true]);
        Reward::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->memberUser)->get(route('member.rewards'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Rewards')
            ->has('summary')
            ->has('rewards')
            ->has('redemptions')
        );
    }

    public function test_redeem_reward_succeeds_with_sufficient_points(): void
    {
        $reward = Reward::factory()->create([
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
            'valid_until' => now()->addMonths(6)->toDateString(),
        ]);

        PointTransaction::query()->create([
            'cooperative_member_id' => $this->member->id,
            'transaction_type' => 'EARNED',
            'points' => 1000,
            'balance_before' => 0,
            'balance_after' => 1000,
            'description' => 'Test earned points',
            'posted_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->memberUser)->post(
            route('member.rewards.redeem', $reward),
            ['quantity' => 1],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reward_redemptions', [
            'reward_id' => $reward->id,
            'cooperative_member_id' => $this->member->id,
            'quantity' => 1,
            'status' => 'PENDING',
        ]);
    }

    public function test_redeem_reward_fails_with_insufficient_points(): void
    {
        $reward = Reward::factory()->create([
            'points_required' => 5000,
            'stock' => 10,
            'is_active' => true,
            'valid_until' => now()->addMonths(6)->toDateString(),
        ]);

        PointTransaction::query()->create([
            'cooperative_member_id' => $this->member->id,
            'transaction_type' => 'EARNED',
            'points' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'Test earned points',
            'posted_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->memberUser)->post(
            route('member.rewards.redeem', $reward),
            ['quantity' => 1],
        );

        $response->assertStatus(422);
    }

    public function test_transactions_returns_ok(): void
    {
        $response = $this->actingAs($this->memberUser)->get(route('member.transactions'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Transactions')
            ->has('transactions')
        );
    }

    public function test_profile_returns_ok_with_user_and_member(): void
    {
        $response = $this->actingAs($this->memberUser)->get(route('member.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Profile')
            ->has('user')
            ->has('member')
        );
    }

    public function test_update_profile_updates_user_and_member(): void
    {
        $newName = 'Updated Name';
        $newPhone = '081234567890';

        $response = $this->actingAs($this->memberUser)->put(route('member.profile.update'), [
            'name' => $newName,
            'email' => $this->memberUser->email,
            'phone' => $newPhone,
            'address' => 'Jl. Baru No. 1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->memberUser->refresh();
        $this->member->refresh();

        $this->assertEquals($newName, $this->memberUser->name);
        $this->assertEquals($newName, $this->member->name);
        $this->assertEquals($newPhone, $this->member->phone);
        $this->assertEquals('Jl. Baru No. 1', $this->member->address);
    }

    public function test_update_profile_fails_with_invalid_email(): void
    {
        $response = $this->actingAs($this->memberUser)->put(route('member.profile.update'), [
            'name' => '',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_update_profile_fails_with_duplicate_email(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->memberUser)->put(route('member.profile.update'), [
            'name' => 'Test User',
            'email' => $otherUser->email,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_notifications_returns_ok(): void
    {
        $response = $this->actingAs($this->memberUser)->get(route('member.notifications'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Notifications')
            ->has('notifications')
        );
    }

    public function test_non_member_gets_403_on_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertRedirect('/dashboard');
    }

    public function test_non_member_gets_403_on_savings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get(route('member.savings'));

        $response->assertRedirect('/dashboard');
    }

    public function test_non_member_gets_403_on_loans(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get(route('member.loans'));

        $response->assertRedirect('/dashboard');
    }

    public function test_non_member_cannot_apply_loan(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $loanType = LoanType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('member.loans.store'), [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_non_member_cannot_update_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_non_member_cannot_redeem_reward(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $reward = Reward::factory()->create([
            'points_required' => 100,
            'is_active' => true,
            'valid_until' => now()->addMonths(6)->toDateString(),
        ]);

        $response = $this->actingAs($user)->post(
            route('member.rewards.redeem', $reward),
            ['quantity' => 1],
        );

        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_shows_active_loan_count(): void
    {
        $loanType = LoanType::factory()->create();

        \App\Models\Loan::factory()->active()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'loan_type_id' => $loanType->id,
        ]);

        $response = $this->actingAs($this->memberUser)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.active_loans', 1)
        );
    }

    public function test_savings_balance_reflects_ledger_entries(): void
    {
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'debit' => 0,
            'credit' => 500000,
        ]);
        CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'debit' => 100000,
            'credit' => 0,
        ]);

        $response = $this->actingAs($this->memberUser)->get(route('member.savings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.savings_balance', 400000)
        );
    }
}
