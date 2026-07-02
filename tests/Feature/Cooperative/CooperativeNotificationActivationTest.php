<?php

namespace Tests\Feature\Cooperative;

use App\Enums\WithdrawalStatus;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\RewardRedemption;
use App\Models\SavingsWithdrawal;
use App\Models\User;
use App\Services\Cooperative\CooperativeNotificationDispatcher;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\LoanService;
use App\Services\Cooperative\MemberValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeNotificationActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Anggota', 'guard_name' => 'web']);
    }

    public function test_member_admin_verified_notifies_pengurus_for_final_approval(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $otherPengurus = $this->roleUser('Pengurus Koperasi', Organization::factory()->create());
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $member = CooperativeMember::factory()
            ->pending()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);

        app(MemberValidationService::class)->verifyByAdmin($member, $admin);

        $this->assertTrue($pengurus->notifications()->where('data->event_type', 'pengurus.member.approval_required')->exists());
        $this->assertFalse($otherPengurus->notifications()->where('data->event_type', 'pengurus.member.approval_required')->exists());
    }

    public function test_member_final_approved_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $member = CooperativeMember::factory()
            ->pendingReview()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);

        app(MemberValidationService::class)->approveFinal($member, $pengurus);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.member.approved')->exists());
    }

    public function test_member_rejected_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $member = CooperativeMember::factory()
            ->pendingReview()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);

        app(MemberValidationService::class)->reject($member, $pengurus, 'Data tidak lengkap.');

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.member.rejected')->exists());
    }

    public function test_member_revision_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $member = CooperativeMember::factory()
            ->pending()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);

        app(MemberValidationService::class)->requestRevision($member, $admin, 'Lengkapi nomor KTP.');

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.member.revision_requested')->exists());
    }

    public function test_member_onboarding_submit_notifies_admin_for_validation(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $otherAdmin = $this->roleUser('Admin Koperasi', Organization::factory()->create());
        $member = CooperativeMember::factory()
            ->pending()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);

        app(CooperativeNotificationDispatcher::class)->memberSubmittedForValidation($member);

        $this->assertTrue($admin->notifications()->where('data->event_type', 'admin.member.validation_required')->exists());
        $this->assertFalse($otherAdmin->notifications()->where('data->event_type', 'admin.member.validation_required')->exists());
    }

    public function test_loan_writeoff_notifies_member_and_pengurus(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $member = $this->memberFor($organization, $memberUser);
        $loan = Loan::factory()->active()->create([
            'organization_id' => $organization->id,
            'cooperative_member_id' => $member->id,
            'loan_type_id' => LoanType::factory()->create()->id,
        ]);

        app(LoanService::class)->writeOff($loan, $pengurus);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.loan.written_off')->exists());
        $this->assertTrue($pengurus->notifications()->where('data->event_type', 'pengurus.loan.written_off')->exists());
    }

    public function test_loan_restructure_request_notifies_member_and_manager(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $manager = $this->roleUser('Manajer Koperasi', $organization);
        $member = $this->memberFor($organization, $memberUser);
        $loan = Loan::factory()->active()->create([
            'organization_id' => $organization->id,
            'cooperative_member_id' => $member->id,
            'loan_type_id' => LoanType::factory()->create()->id,
        ]);

        app(LoanRestructureService::class)->request($loan, [
            'reason' => 'Sulit bayar',
            'proposed_principal_amount' => $loan->outstanding_amount,
            'proposed_interest_rate' => $loan->interest_rate,
            'proposed_term_months' => $loan->term_months,
            'proposed_first_due_date' => now()->addMonth()->toDateString(),
        ], $memberUser);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.loan.restructure_requested')->exists());
        $this->assertTrue($manager->notifications()->where('data->event_type', 'manager.loan.restructure_review_required')->exists());
    }

    public function test_pos_sale_completed_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $member = $this->memberFor($organization, $memberUser);
        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-TEST-001',
            'cooperative_member_id' => $member->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'gross_profit' => 10000,
            'status' => 'COMPLETED',
            'sold_at' => now()->toDateTimeString(),
        ]);

        app(CooperativeNotificationDispatcher::class)->posSaleCompleted($transaction);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.pos.sale_completed')->exists());
    }

    public function test_pos_void_flow_notifies_pengurus_member_and_requester(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $cashier = $this->roleUser('Kasir Koperasi', $organization);
        $member = $this->memberFor($organization, $memberUser);
        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-TEST-002',
            'cooperative_member_id' => $member->id,
            'subtotal' => 75000,
            'total_amount' => 75000,
            'gross_profit' => 15000,
            'status' => 'COMPLETED',
            'sold_at' => now()->toDateTimeString(),
        ]);
        $voidRequest = PosVoidRequest::query()->create([
            'pos_transaction_id' => $transaction->id,
            'requested_by' => $cashier->id,
            'reason' => 'Salah input',
            'status' => PosVoidRequest::STATUS_PENDING,
        ]);
        $dispatcher = app(CooperativeNotificationDispatcher::class);

        $dispatcher->posVoidRequested($transaction, $cashier, $organization->id);
        $this->assertTrue($pengurus->notifications()->where('data->event_type', 'pengurus.pos.void_required')->exists());

        $transaction->update(['status' => 'VOIDED']);
        $dispatcher->posVoidApproved($transaction->refresh(), $voidRequest, $pengurus);
        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.pos.voided')->exists());
        $this->assertTrue($cashier->notifications()->where('data->event_type', 'cashier.pos.void_approved')->exists());

        $dispatcher->posVoidRejected($transaction->refresh(), $voidRequest, $pengurus);
        $this->assertTrue($cashier->notifications()->where('data->event_type', 'cashier.pos.void_rejected')->exists());
    }

    public function test_withdrawal_requested_notifies_admin_and_pengurus(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $member = $this->memberFor($organization, $memberUser);
        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'amount' => 100000,
            'status' => WithdrawalStatus::Pending,
        ]);

        app(CooperativeNotificationDispatcher::class)->withdrawalRequested($withdrawal);

        $this->assertTrue($admin->notifications()->where('data->event_type', 'admin.withdrawal.requested')->exists());
        $this->assertTrue($pengurus->notifications()->where('data->event_type', 'admin.withdrawal.requested')->exists());
    }

    public function test_withdrawal_approved_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $member = $this->memberFor($organization, $memberUser);
        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'amount' => 50000,
            'status' => WithdrawalStatus::Processed,
        ]);

        app(CooperativeNotificationDispatcher::class)->withdrawalApproved($withdrawal, $pengurus);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.withdrawal.approved')->exists());
    }

    public function test_points_earned_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $member = $this->memberFor($organization, $memberUser);
        $pointTransaction = PointTransaction::factory()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 120,
            'balance_after' => 120,
        ]);

        app(CooperativeNotificationDispatcher::class)->pointsEarned($member->refresh(), $pointTransaction);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.points.earned')->exists());
    }

    public function test_points_redeemed_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $member = $this->memberFor($organization, $memberUser);
        $pointTransaction = PointTransaction::factory()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'REDEEMED',
            'points' => -300,
            'balance_after' => 200,
            'metadata' => ['reward_name' => 'Voucher Belanja'],
        ]);

        app(CooperativeNotificationDispatcher::class)->pointsRedeemed($member->refresh(), $pointTransaction);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.points.redeemed')->exists());
    }

    public function test_reward_status_changed_notifies_member(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $member = $this->memberFor($organization, $memberUser);
        $redemption = RewardRedemption::factory()->create([
            'cooperative_member_id' => $member->id,
            'status' => 'SHIPPED',
        ]);

        app(CooperativeNotificationDispatcher::class)->rewardStatusChanged($redemption);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.reward.status_changed')->exists());
    }

    public function test_dispatcher_deduplication_prevents_duplicate_notifications(): void
    {
        [$organization, $memberUser] = $this->setupOrganizationWithMember();
        $member = $this->memberFor($organization, $memberUser);
        $pointTransaction = PointTransaction::factory()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 50,
            'balance_after' => 50,
        ]);
        $dispatcher = app(CooperativeNotificationDispatcher::class);

        $dispatcher->pointsEarned($member->refresh(), $pointTransaction);
        $dispatcher->pointsEarned($member->refresh(), $pointTransaction);

        $count = $memberUser->notifications()->where('data->event_type', 'member.points.earned')->count();
        $this->assertSame(1, $count);
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function setupOrganizationWithMember(): array
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);

        return [$organization, $memberUser];
    }

    private function memberFor(Organization $organization, User $user): CooperativeMember
    {
        return CooperativeMember::factory()
            ->active()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ]);
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
