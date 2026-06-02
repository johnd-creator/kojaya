<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\PermissionEnum;
use App\Enums\VendorStatus;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanRestructure;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionReadinessP0P2Test extends TestCase
{
    use RefreshDatabase;

    public function test_member_profile_response_uses_allowlisted_contract(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.member.id', $member->id)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token')
            ->assertJsonMissingPath('data.user.organization_id')
            ->assertJsonMissingPath('data.member.organization_id')
            ->assertJsonMissingPath('data.member.identity_number')
            ->assertJsonMissingPath('data.member.notes');
    }

    public function test_loan_restructure_approval_applies_new_schedule(): void
    {
        $actor = User::factory()->create();
        $loan = Loan::factory()->active()->create([
            'outstanding_amount' => 1200000,
        ]);
        LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);

        $restructure = app(LoanRestructureService::class)->request($loan, [
            'reason' => 'Penyesuaian kemampuan bayar.',
            'proposed_principal_amount' => 1200000,
            'proposed_interest_rate' => 1,
            'proposed_term_months' => 12,
            'proposed_first_due_date' => now()->addMonth()->toDateString(),
        ], $actor);

        $applied = app(LoanRestructureService::class)->approveAndApply($restructure, $actor, 'Disetujui komite.');

        $loan->refresh();

        $this->assertSame('APPROVED', $applied->status);
        $this->assertSame(LoanStatus::Active, $loan->status);
        $this->assertSame('1200000.00', $loan->principal_amount);
        $this->assertSame(12, $loan->installments()->count());
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => LoanRestructure::class,
            'subject_id' => (string) $restructure->id,
            'to_status' => 'APPROVED',
        ]);
    }

    public function test_written_off_status_is_available_and_audited(): void
    {
        $actor = User::factory()->create();
        $loan = Loan::factory()->active()->create();

        $writtenOff = app(LoanService::class)->writeOff($loan, $actor, 'Keputusan rapat pengurus.');

        $this->assertSame(LoanStatus::WrittenOff, $writtenOff->status);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'to_status' => LoanStatus::WrittenOff->value,
        ]);
    }

    public function test_purchase_order_rejects_suspended_or_blacklisted_vendor(): void
    {
        $vendor = Vendor::factory()->create([
            'status' => VendorStatus::Blacklisted,
        ]);

        $this->expectException(ValidationException::class);

        PurchaseOrder::factory()->create([
            'organization_id' => $vendor->organization_id,
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_new_policy_coverage_for_resource_hardening(): void
    {
        foreach ([
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
            PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value,
            PermissionEnum::VENDORS_MANAGE->value,
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo([
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
            PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value,
            PermissionEnum::VENDORS_MANAGE->value,
        ]);

        $loan = Loan::factory()->active()->create();
        $restructure = LoanRestructure::query()->create([
            'loan_id' => $loan->id,
            'cooperative_member_id' => $loan->cooperative_member_id,
            'status' => 'PENDING',
            'reason' => 'Review policy.',
            'proposed_principal_amount' => $loan->outstanding_amount,
            'proposed_interest_rate' => $loan->interest_rate,
            'proposed_term_months' => $loan->term_months,
            'proposed_first_due_date' => now()->addMonth()->toDateString(),
        ]);
        $vendor = Vendor::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue(Gate::forUser($user)->allows('approve', $restructure));
        $this->assertTrue(Gate::forUser($user)->allows('manage', Vendor::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $vendor));
    }

    public function test_payment_go_live_checklist_documents_external_p0_validation(): void
    {
        $checklist = file_get_contents(base_path('docs/payment-go-live-checklist.md'));

        $this->assertStringContainsString('MIDTRANS_SERVER_KEY', $checklist);
        $this->assertStringContainsString('live transaction', $checklist);
        $this->assertStringContainsString('WhatsApp template', $checklist);
        $this->assertStringContainsString('rollback', $checklist);
    }
}
