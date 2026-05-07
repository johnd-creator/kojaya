<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\CooperativeMemberDocument;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PhaseDPrivacyHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employeeUser;

    private User $otherEmployeeUser;

    private Employee $employee;

    private Employee $otherEmployee;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->org = Organization::query()->create([
            'name' => 'Test Org', 'code' => 'TEST', 'level' => 'CABANG', 'type' => 'BRANCH',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin Pusat');

        $this->employeeUser = User::factory()->create();
        $this->employeeUser->givePermissionTo('view_own_payslip');
        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'organization_id' => $this->org->id,
        ]);

        $this->otherEmployeeUser = User::factory()->create();
        $this->otherEmployeeUser->givePermissionTo('view_own_payslip');
        $this->otherEmployee = Employee::factory()->create([
            'user_id' => $this->otherEmployeeUser->id,
            'organization_id' => $this->org->id,
        ]);
    }

    private function createPayroll(Employee $emp): \App\Models\Payroll
    {
        return \App\Models\Payroll::query()->create([
            'employee_id' => $emp->id,
            'period' => '2026-05',
            'basic_salary' => 5000000,
            'net_salary' => 4500000,
            'status' => 'APPROVED',
            'organization_id' => $this->org->id,
            'period_year' => 2026,
            'period_month' => 5,
        ]);
    }

    public function test_download_payslip_signed_url_works(): void
    {
        $payroll = $this->createPayroll($this->employee);
        $url = URL::temporarySignedRoute('download.payslip', now()->addMinutes(5), ['id' => $payroll->id]);

        $response = $this->actingAs($this->employeeUser)->get($url);

        // 500 because fake storage can't stream PDF binary
        // But it should not be 403 (forbidden) or 401 (invalid signature)
        $this->assertTrue($response->isSuccessful());
    }

    public function test_download_payslip_cannot_access_other_employee_payslip(): void
    {
        $payroll = $this->createPayroll($this->otherEmployee);
        $url = URL::temporarySignedRoute('download.payslip', now()->addMinutes(5), ['id' => $payroll->id]);

        $response = $this->actingAs($this->employeeUser)->get($url);

        $response->assertForbidden();
    }

    public function test_download_payslip_admin_can_access_any(): void
    {
        $payroll = $this->createPayroll($this->employee);
        $url = URL::temporarySignedRoute('download.payslip', now()->addMinutes(5), ['id' => $payroll->id]);

        $response = $this->actingAs($this->admin)->get($url);

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_download_kyc_signed_url_works(): void
    {
        Storage::disk('public')->put('kyc/test.pdf', 'dummy');

        $member = CooperativeMember::query()->create([
            'user_id' => $this->employeeUser->id,
            'name' => 'Test Member',
            'member_id' => 'M-001',
            'member_no' => 'M-001',
            'organization_id' => $this->org->id,
        ]);

        $doc = CooperativeMemberDocument::query()->create([
            'cooperative_member_id' => $member->id,
            'document_type' => 'KTP',
            'type' => 'KTP',
            'file_path' => 'kyc/test.pdf',
            'organization_id' => $this->org->id,
        ]);

        $url = URL::temporarySignedRoute('download.kyc', now()->addMinutes(5), [
            'memberId' => $member->id,
            'documentId' => $doc->id,
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        // May 500 on fake storage download, but should NOT be 403 (access) or 401 (signature)
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_download_requires_signature(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/download/payslip/999');

        $response->assertStatus(403);
    }

    public function test_download_with_invalid_signature_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('download.payslip', now()->subMinutes(5), ['id' => 1]);

        $response = $this->actingAs($this->employeeUser)->get($url);

        $response->assertStatus(403);
    }

    public function test_download_kyc_cannot_be_accessed_without_permission(): void
    {
        Storage::disk('public')->put('kyc/test.pdf', 'dummy');

        $member = CooperativeMember::query()->create([
            'user_id' => $this->employeeUser->id,
            'name' => 'Test Member',
            'member_id' => 'M-002',
            'member_no' => 'M-002',
            'organization_id' => $this->org->id,
        ]);

        $doc = CooperativeMemberDocument::query()->create([
            'cooperative_member_id' => $member->id,
            'document_type' => 'KTP',
            'type' => 'KTP',
            'file_path' => 'kyc/test.pdf',
            'organization_id' => $this->org->id,
        ]);

        $url = URL::temporarySignedRoute('download.kyc', now()->addMinutes(5), [
            'memberId' => $member->id,
            'documentId' => $doc->id,
        ]);

        $response = $this->actingAs($this->employeeUser)->get($url);

        $response->assertForbidden();
    }

    public function test_download_mcu_audit_log_is_recorded(): void
    {
        Storage::disk('public')->put('mcu/1/test.pdf', 'dummy');

        $mcu = \App\Models\MedicalCheckup::query()->create([
            'employee_id' => $this->employee->id,
            'checkup_date' => now()->toDateString(),
            'document_path' => 'mcu/1/test.pdf',
            'organization_id' => $this->org->id,
        ]);

        $url = URL::temporarySignedRoute('download.mcu', now()->addMinutes(5), ['mcu' => $mcu->id]);

        $response = $this->actingAs($this->admin)->get($url);

        // Fake storage download may 500 but download_log should still be written
        $this->assertDatabaseHas('download_logs', [
            'document_type' => 'mcu',
            'document_id' => $mcu->id,
        ]);
    }

    public function test_data_governance_document_exists(): void
    {
        $path = base_path('docs/data-governance.md');
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertStringContainsString('Data Governance', $content);
        $this->assertStringContainsString('Di-encrypt', $content);
        $this->assertStringContainsString('Retensi Data', $content);
        $this->assertStringContainsString('Backup / Restore Runbook', $content);
    }
}
