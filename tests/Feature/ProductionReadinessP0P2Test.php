<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\PermissionEnum;
use App\Enums\VendorStatus;
use App\Http\Resources\CooperativeMemberResource;
use App\Http\Resources\MemberSelfServiceResource;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanRestructure;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogService;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\LoanService;
use App\Services\Cooperative\MemberProfileService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function test_member_profile_sync_rolls_back_when_user_update_fails(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id, 'email' => 'member@example.test']);
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'name' => 'Nama Lama',
            'email' => $user->email,
        ]);
        User::factory()->create(['email' => 'existing@example.test']);

        try {
            app(MemberProfileService::class)->update($user, $member, [
                'name' => 'Nama Baru',
                'email' => 'existing@example.test',
            ]);
            $this->fail('Expected duplicate user email to fail.');
        } catch (QueryException) {
            $this->assertSame('Nama Lama', $member->refresh()->name);
            $this->assertSame('member@example.test', $member->email);
        }
    }

    public function test_sso_member_email_change_requires_a_verified_account_flow(): void
    {
        $user = User::factory()->create(['email' => 'sso-member@example.test']);
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'sso_provider' => 'google',
        ]);

        $this->expectException(ValidationException::class);

        app(MemberProfileService::class)->update($user, $member, [
            'name' => $member->name,
            'email' => 'changed@example.test',
        ]);
    }

    public function test_cooperative_member_resource_masks_sensitive_data_without_the_dedicated_permission(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234567890',
        ]);
        $request = Request::create('/api/v1/members/'.$member->id);
        $request->setUserResolver(fn (): User => $user);

        $masked = (new CooperativeMemberResource($member))->toArray($request);

        $this->assertNotSame($member->identity_number, $masked['identity_number']);
        $this->assertNotSame($member->npwp, $masked['npwp']);
        $this->assertSame('******7890', $masked['no_rekening']);

        Permission::firstOrCreate(['name' => PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value);

        $unmasked = (new CooperativeMemberResource($member))->toArray($request);

        $this->assertSame($member->identity_number, $unmasked['identity_number']);
        $this->assertSame($member->npwp, $unmasked['npwp']);
        $this->assertSame($member->no_rekening, $unmasked['no_rekening']);
    }

    public function test_loan_resource_does_not_leak_member_pii_to_cooperative_staff(): void
    {
        $organization = Organization::factory()->create();
        $staff = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'identity_number' => '3201234567890001',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234567890',
        ]);
        $loan = Loan::factory()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
        ]);
        $request = Request::create('/api/v1/loans/'.$loan->id);
        $request->setUserResolver(fn (): User => $staff);

        $data = (new MemberSelfServiceResource($loan->member))->resolve($request);

        $this->assertSame('******7890', $data['bank_account_number']);
        $this->assertNull($data['address']);
        $this->assertNull($data['bank_name']);
    }

    public function test_audit_contract_records_context_and_redacts_sensitive_values(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create(['organization_id' => $organization->id]);
        $correlationId = 'bf280c9e-8c6e-4d19-891e-5ab41a65f2af';

        $this->actingAs($actor);
        app('request')->headers->set('X-Correlation-ID', $correlationId);

        app(AuditLogService::class)->log('member.pii.viewed', 'cooperative.member', $member, [
            'new' => [
                'token' => 'must-not-be-stored',
                'identity_number' => '3201234567890001',
            ],
            'reason' => 'Verifikasi data anggota.',
        ]);

        $audit = AuditLog::query()->where('action', 'member.pii.viewed')->sole();

        $this->assertSame($correlationId, $audit->correlation_id);
        $this->assertSame($organization->id, $audit->organization_id);
        $this->assertSame('Verifikasi data anggota.', $audit->reason);
        $this->assertSame('[REDACTED]', $audit->new_values['token']);
        $this->assertSame('[REDACTED]', $audit->new_values['identity_number']);
        $this->assertNotNull($audit->occurred_at);
    }

    public function test_new_member_sensitive_fields_are_encrypted_and_indexed(): void
    {
        $member = CooperativeMember::factory()->create([
            'identity_number' => '3201234567890001',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234567890',
        ]);

        $raw = DB::table('cooperative_members')->where('id', $member->id)->first();

        $this->assertSame('3201234567890001', $raw->identity_number);
        $this->assertNotSame('3201234567890001', $raw->identity_number_enc);
        $this->assertSame(CooperativeMember::blindIndexFor('identity_number', '3201234567890001'), $raw->identity_number_bidx);
        $this->assertSame('3201234567890001', $member->identity_number);
        $this->assertSame('12.345.678.9-012.000', $member->npwp);
        $this->assertSame('1234567890', $member->no_rekening);
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
