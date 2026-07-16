<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceReportQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_compliant_endpoint_identifies_employees_missing_valid_certificate_or_future_mcu(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => null]);
        $actor->assignRole('System Admin');
        Sanctum::actingAs($actor, ['reports:read']);

        // Fully compliant: valid certificate and a future MCU.
        $compliant = Employee::factory()->create([
            'organization_id' => $organization->id,
            'employee_code' => 'COMP-1',
            'first_name' => 'Compliant',
        ]);
        EmployeeCertificate::query()->create([
            'employee_id' => $compliant->id,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-VALID-1',
            'issue_date' => now()->subMonths(6)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'issuing_authority' => 'LSP',
            'status' => CertificateStatus::VALID,
        ]);
        MedicalCheckup::query()->create([
            'employee_id' => $compliant->id,
            'checkup_date' => now()->subMonth()->toDateString(),
            'next_checkup_date' => now()->addMonths(6)->toDateString(),
            'result' => McuResult::FIT,
            'fit_to_work' => true,
            'doctor_name' => 'Dr. Fit',
            'clinic_name' => 'Klinik Sehat',
        ]);

        // Non-compliant: expired certificate and only past MCU.
        $noValidCert = Employee::factory()->create([
            'organization_id' => $organization->id,
            'employee_code' => 'NCERT-1',
            'first_name' => 'NoValidCert',
        ]);
        EmployeeCertificate::query()->create([
            'employee_id' => $noValidCert->id,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-EXP-1',
            'issue_date' => now()->subYears(2)->toDateString(),
            'expiry_date' => now()->subYear()->toDateString(),
            'issuing_authority' => 'LSP',
            'status' => CertificateStatus::EXPIRED,
        ]);
        MedicalCheckup::query()->create([
            'employee_id' => $noValidCert->id,
            'checkup_date' => now()->subYear()->toDateString(),
            'next_checkup_date' => now()->subMonth()->toDateString(),
            'result' => McuResult::FIT,
            'fit_to_work' => true,
            'doctor_name' => 'Dr. Late',
            'clinic_name' => 'Klinik Lama',
        ]);

        // Non-compliant: valid certificate but no future MCU.
        $noFutureMcu = Employee::factory()->create([
            'organization_id' => $organization->id,
            'employee_code' => 'NMCU-1',
            'first_name' => 'NoFutureMcu',
        ]);
        EmployeeCertificate::query()->create([
            'employee_id' => $noFutureMcu->id,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-VALID-2',
            'issue_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'issuing_authority' => 'LSP',
            'status' => CertificateStatus::VALID,
        ]);

        $response = $this->getJson('/api/reports/non-compliant-employees');

        $response->assertOk();

        $codes = collect($response->json('data.data'))->pluck('employee_code')->all();

        $this->assertContains('NCERT-1', $codes);
        $this->assertContains('NMCU-1', $codes);
        $this->assertNotContains('COMP-1', $codes);
    }

    public function test_non_compliant_endpoint_query_does_not_reference_select_aliases_within_having(): void
    {
        // Static guard: protects against regressing back to alias-based HAVING,
        // which is not portable to PostgreSQL.
        $source = (string) file_get_contents(
            app_path('Http/Controllers/ComplianceReportController.php')
        );

        $this->assertStringContainsString('COUNT(DISTINCT ec.id)', $source);
        $this->assertStringContainsString('MAX(mc.next_checkup_date) IS NULL', $source);
        $this->assertStringNotContainsString("havingRaw('valid_certificates", $source);
        $this->assertStringNotContainsString("havingRaw('next_mcu_date", $source);
        $this->assertStringNotContainsString('next_mcu_date < ?', $source);
    }

    public function test_non_compliant_endpoint_preserves_pagination_response_contract(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => null]);
        $actor->assignRole('System Admin');
        Sanctum::actingAs($actor, ['reports:read']);

        Employee::factory()->count(3)->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->getJson('/api/reports/non-compliant-employees?per_page=2');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.per_page', 2);
    }

    public function test_non_compliant_endpoint_scopes_results_to_actor_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $actor = User::factory()->create(['organization_id' => $orgA->id]);
        $actor->assignRole('System Admin');
        Sanctum::actingAs($actor, ['reports:read']);

        $scoped = Employee::factory()->create([
            'organization_id' => $orgA->id,
            'employee_code' => 'ORG-A-1',
        ]);
        $other = Employee::factory()->create([
            'organization_id' => $orgB->id,
            'employee_code' => 'ORG-B-1',
        ]);

        $response = $this->getJson('/api/reports/non-compliant-employees');

        $response->assertOk();

        $codes = collect($response->json('data.data'))->pluck('employee_code')->all();

        $this->assertContains('ORG-A-1', $codes);
        $this->assertNotContains('ORG-B-1', $codes);
    }
}
