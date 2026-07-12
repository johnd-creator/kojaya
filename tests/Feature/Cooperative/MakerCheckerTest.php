<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberValidationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MakerCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_verifier_cannot_be_final_approver(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $organization->id,
        ]);

        // Admin verifies the member
        app(MemberValidationService::class)->verifyByAdmin($member, $admin);

        // Same admin tries to do final approval - should fail
        $this->expectException(ValidationException::class);

        try {
            app(MemberValidationService::class)->approveFinal($member->refresh(), $admin);
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'Verifier administrasi tidak boleh menjadi approver final.',
                collect($e->validator->errors()->all())->join(' '),
            );

            throw $e;
        }
    }

    public function test_different_user_can_be_final_approver(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $organization->id,
        ]);

        // Admin verifies
        app(MemberValidationService::class)->verifyByAdmin($member, $admin);

        // Different user (Pengurus) does final approval - should succeed
        $result = app(MemberValidationService::class)->approveFinal($member->refresh(), $pengurus);

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $result->validation_status);
    }

    public function test_member_without_admin_verifier_allows_any_approver(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $organization->id,
            'admin_validated_by' => null,
        ]);

        // No prior verifier, so any approver should be allowed
        $result = app(MemberValidationService::class)->approveFinal($member, $pengurus);

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $result->validation_status);
    }

    public function test_loan_manager_reviewer_cannot_be_final_approver(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $manager = User::factory()->create(['organization_id' => $organization->id]);
        $manager->assignRole('Manajer Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);
        $loanType = \App\Models\LoanType::factory()->create();

        $loanService = app(\App\Contracts\Cooperative\LoanServiceContract::class);
        $loan = $loanService->apply([
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 3,
            'first_due_date' => now()->addMonth()->toDateString(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
        ], $admin);

        // Manager reviews
        $loanService->managerReview($loan, $manager);

        // Same manager tries to do final approval - should fail
        $this->expectException(ValidationException::class);
        $loanService->approve($loan->refresh(), $manager);
    }

    public function test_payment_recorder_cannot_approve_own_payment(): void
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->assignRole('Kasir Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);

        $payment = \App\Models\CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'user_id' => $cashier->id,
        ]);

        $this->expectException(ValidationException::class);
        app(\App\Services\Cooperative\CooperativePaymentService::class)->approve($payment, $cashier);
    }
}
