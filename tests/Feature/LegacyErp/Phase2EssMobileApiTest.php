<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\OvertimeRule;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\ShiftRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase2EssMobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ess_attendance_stores_mobile_location_device_and_shift_roster(): void
    {
        [$user, $employee, $organization] = $this->employeeUser();
        $employee->update(['shift_group' => 'A']);
        ShiftRoster::factory()->create([
            'date' => today()->toDateString(),
            'shift_group' => 'A',
        ]);

        Sanctum::actingAs($user, ['attendance:read', 'attendance:write', 'ess:read']);

        $this->postJson('/api/ess/attendance/check-in', [
            'latitude' => $organization->latitude,
            'longitude' => $organization->longitude,
            'accuracy' => 8,
            'device_id' => 'android-hr-1',
        ])->assertOk()
            ->assertJsonPath('data.clock_in_device_id', 'android-hr-1');

        $this->postJson('/api/ess/attendance/check-out', [
            'latitude' => $organization->latitude,
            'longitude' => $organization->longitude,
            'accuracy' => 9,
            'device_id' => 'android-hr-1',
        ])->assertOk()
            ->assertJsonPath('data.clock_out_device_id', 'android-hr-1');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'clock_in_device_id' => 'android-hr-1',
            'clock_out_device_id' => 'android-hr-1',
            'clock_in_accuracy' => 8,
            'clock_out_accuracy' => 9,
        ]);

        $this->getJson('/api/ess/shift-roster')
            ->assertOk()
            ->assertJsonPath('0.shift_group', 'A');
    }

    public function test_ess_leave_balance_create_cancel_and_ownership_are_scoped_to_employee(): void
    {
        [$user, $employee] = $this->employeeUser();
        $leaveType = LeaveType::factory()->create([
            'default_days_allowance' => 12,
            'requires_attachment' => false,
        ]);
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->startOfYear()->addWeek()->toDateString(),
            'end_date' => now()->startOfYear()->addWeek()->toDateString(),
            'total_days' => 1,
            'status' => 'Approved',
        ]);
        $otherLeave = Leave::factory()->create([
            'leave_type_id' => $leaveType->id,
            'status' => 'Pending',
        ]);

        Sanctum::actingAs($user, ['ess:read', 'ess:write']);

        $this->getJson('/api/ess/leaves')
            ->assertOk()
            ->assertJsonPath('balance.0.remaining', 11);

        $leaveId = $this->postJson('/api/ess/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => today()->addWeek()->toDateString(),
            'end_date' => today()->addWeek()->addDay()->toDateString(),
            'reason' => 'Keperluan keluarga',
        ])->assertCreated()
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.status', 'Pending')
            ->json('data.id');

        $this->postJson('/api/ess/leaves/'.$leaveId.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.cancel_requested_by', $user->id);

        $this->postJson('/api/ess/leaves/'.$otherLeave->id.'/cancel')
            ->assertForbidden();
    }

    public function test_ess_overtime_reimbursement_payslip_compliance_and_notifications_are_available(): void
    {
        Storage::fake('public');
        [$user, $employee, $organization] = $this->employeeUser();
        $rule = OvertimeRule::factory()->create([
            'organization_id' => $organization->id,
            'min_hours' => 0,
            'max_hours_daily' => 4,
            'requires_approval' => true,
            'is_active' => true,
        ]);
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'period' => now()->format('Y-m'),
            'status' => 'PAID',
            'net_salary' => 7500000,
        ]);
        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'period' => now()->subMonth()->format('Y-m'),
            'status' => 'DRAFT',
        ]);
        EmployeeCertificate::query()->create([
            'employee_id' => $employee->id,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-001',
            'issue_date' => today()->subYear()->toDateString(),
            'expiry_date' => today()->addYear()->toDateString(),
            'issuing_authority' => 'HR',
            'status' => CertificateStatus::VALID,
        ]);
        MedicalCheckup::query()->create([
            'employee_id' => $employee->id,
            'checkup_date' => today()->subMonth()->toDateString(),
            'next_checkup_date' => today()->addMonths(11)->toDateString(),
            'result' => McuResult::FIT,
            'fit_to_work' => true,
        ]);
        Reimbursement::factory()->create();
        $user->notify(new class extends \Illuminate\Notifications\Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['message' => 'Payroll sudah tersedia'];
            }
        });

        Sanctum::actingAs($user, ['ess:read', 'ess:write']);

        $this->postJson('/api/ess/overtime', [
            'overtime_rule_id' => $rule->id,
            'date' => today()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'Closing bulanan',
        ])->assertCreated()
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.status', 'PENDING');

        $this->getJson('/api/ess/overtime')
            ->assertOk()
            ->assertJsonPath('data.0.employee_id', $employee->id);

        $reimbursementId = $this->postJson('/api/ess/reimbursements', [
            'description' => 'Klaim transport',
            'items' => [
                [
                    'category' => 'TRANSPORT',
                    'description' => 'Ojek online',
                    'amount' => 50000,
                    'receipt_date' => today()->toDateString(),
                    'receipt_file' => UploadedFile::fake()->image('receipt.jpg'),
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'SUBMITTED')
            ->json('data.id');

        $this->getJson('/api/ess/reimbursements')
            ->assertOk()
            ->assertJsonPath('data.0.id', $reimbursementId);

        $this->getJson('/api/ess/payslips')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.net_salary', 7500000);

        $this->get('/api/ess/payslips/'.$payroll->id.'/download')
            ->assertOk();

        $this->getJson('/api/ess/compliance')
            ->assertOk()
            ->assertJsonPath('data.certificates.0.certificate_number', 'CERT-001')
            ->assertJsonPath('data.medical_checkups.0.fit_to_work', true);

        $this->getJson('/api/ess/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.data.message', 'Payroll sudah tersedia');
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Employee, 2: \App\Models\Organization}
     */
    private function employeeUser(): array
    {
        $organization = Organization::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 500,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'email' => $user->email,
        ]);

        return [$user, $employee, $organization];
    }
}
