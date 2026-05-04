<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class P5EssPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_ess_pages_render_for_linked_employee(): void
    {
        [$user, $employee] = $this->createEmployeeUser();

        Payroll::query()->create([
            'employee_id' => $employee->id,
            'organization_id' => $employee->organization_id,
            'period' => now()->format('Y-m'),
            'basic_salary' => 5000000,
            'total_allowance' => 500000,
            'total_deduction' => 250000,
            'tax_amount' => 100000,
            'bpjs_amount' => 50000,
            'net_salary' => 5100000,
            'status' => 'PAID',
        ]);

        EmployeeCertificate::query()->create([
            'employee_id' => $employee->id,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-001',
            'issue_date' => now()->subMonths(6)->toDateString(),
            'expiry_date' => now()->addMonth()->toDateString(),
            'issuing_authority' => 'LSP',
            'status' => CertificateStatus::VALID,
        ]);

        MedicalCheckup::query()->create([
            'employee_id' => $employee->id,
            'checkup_date' => now()->subMonths(11)->toDateString(),
            'next_checkup_date' => now()->addWeeks(2)->toDateString(),
            'result' => McuResult::FIT,
            'fit_to_work' => true,
            'doctor_name' => 'Dr. Fit',
            'clinic_name' => 'Klinik Sehat',
        ]);

        $this->actingAs($user)
            ->get('/ess')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ESS/Dashboard')
                ->where('stats.expiring_certificates', 1)
                ->where('stats.due_medical_checkups', 1)
            );

        $this->actingAs($user)
            ->get('/ess/compliance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ESS/Compliance')
                ->has('certificates', 1)
                ->has('medicalCheckups', 1)
            );
    }

    public function test_ess_profile_can_be_updated(): void
    {
        [$user, $employee] = $this->createEmployeeUser();

        $this->actingAs($user)
            ->put('/ess/profile', [
                'name' => 'Karyawan Baru',
                'email' => 'karyawan-baru@example.com',
                'birth_date' => '1992-01-05',
                'gender' => 'MALE',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Karyawan Baru',
            'email' => 'karyawan-baru@example.com',
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'email' => 'karyawan-baru@example.com',
        ]);
    }

    private function createEmployeeUser(): array
    {
        Role::query()->firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('Employee');

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [$user, $employee];
    }
}
