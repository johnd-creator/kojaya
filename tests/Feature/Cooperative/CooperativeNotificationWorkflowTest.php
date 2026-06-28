<?php

namespace Tests\Feature\Cooperative;

use App\Enums\LoanStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_review_notifies_member_and_pengurus_for_final_approval(): void
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $manager = $this->roleUser('Manajer Koperasi', $organization);
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);
        $otherPengurus = $this->roleUser('Pengurus Koperasi', Organization::factory()->create());
        $member = CooperativeMember::factory()
            ->active()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);
        $loan = Loan::factory()->create([
            'organization_id' => $organization->id,
            'cooperative_member_id' => $member->id,
            'loan_type_id' => LoanType::factory()->create()->id,
            'user_id' => $memberUser->id,
            'status' => LoanStatus::Applied,
        ]);

        app(LoanService::class)->managerReview($loan, $manager, 'Layak diproses final.');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $memberUser->id,
        ]);
        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.loan.manager_reviewed')->exists());
        $this->assertTrue($pengurus->notifications()->where('data->event_type', 'pengurus.loan.final_approval_required')->exists());
        $this->assertFalse($otherPengurus->notifications()->where('data->event_type', 'pengurus.loan.final_approval_required')->exists());
    }

    public function test_recording_and_approving_payment_notifies_admin_and_member(): void
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $admin = $this->roleUser('Admin Koperasi', $organization);
        $approver = $this->roleUser('Pengurus Koperasi', $organization);
        $member = CooperativeMember::factory()
            ->active()
            ->create([
                'organization_id' => $organization->id,
                'user_id' => $memberUser->id,
            ]);
        $contributionType = CooperativeContributionType::factory()->create([
            'code' => 'SUKARELA-'.fake()->unique()->numerify('###'),
            'category' => 'SUKARELA',
            'default_amount' => 0,
        ]);

        $payment = app(CooperativePaymentService::class)->record([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'amount' => 125000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'reference_no' => 'TRX-001',
        ], $memberUser);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.payment.proof_uploaded')->exists());
        $this->assertTrue($admin->notifications()->where('data->event_type', 'admin.payment.approval_required')->exists());

        app(CooperativePaymentService::class)->approve($payment, $approver);

        $this->assertTrue($memberUser->notifications()->where('data->event_type', 'member.payment.approved')->exists());
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
