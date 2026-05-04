<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_reports_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports')
            );
    }

    public function test_consolidated_stats_endpoint_returns_employee_summary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Head Office']);
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('reports.consolidated-stats'))
            ->assertOk()
            ->assertJsonPath('data.total_employees', 1)
            ->assertJsonStructure([
                'data' => [
                    'total_employees',
                    'by_organization',
                ],
                'message',
            ]);
    }

    public function test_consolidated_payroll_endpoint_returns_aggregated_totals(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Regional A']);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'period' => '2026-02',
            'basic_salary' => 4000000,
            'total_allowance' => 1000000,
            'net_salary' => 4500000,
        ]);
        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'period' => '2026-03',
            'basic_salary' => 5000000,
            'total_allowance' => 500000,
            'net_salary' => 4700000,
        ]);

        $this->actingAs($user)
            ->getJson(route('reports.consolidated-payroll', [
                'period_from' => '2026-02',
                'period_to' => '2026-03',
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.organization_id', $organization->id)
            ->assertJsonPath('data.0.employee_count', 2)
            ->assertJsonPath('data.0.total_gross', 10500000)
            ->assertJsonPath('data.0.total_net', 9200000);
    }

    public function test_consolidated_attendance_endpoint_returns_monthly_summary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employeeA = Employee::factory()->create(['organization_id' => $organization->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $organization->id]);

        Attendance::factory()->create([
            'employee_id' => $employeeA->id,
            'organization_id' => $organization->id,
            'date' => '2026-02-10',
            'clock_in' => '08:00:00',
            'status' => 'PRESENT',
        ]);
        Attendance::factory()->create([
            'employee_id' => $employeeB->id,
            'organization_id' => $organization->id,
            'date' => '2026-02-10',
            'clock_in' => null,
            'status' => 'ABSENT',
        ]);

        $this->actingAs($user)
            ->getJson(route('reports.consolidated-attendance', ['month' => '2026-02']))
            ->assertOk()
            ->assertJsonPath('data.period', '2026-02')
            ->assertJsonPath('data.by_organization.0.organization_id', $organization->id)
            ->assertJsonPath('data.by_organization.0.total_employees', 2)
            ->assertJsonPath('data.by_organization.0.total_present', 1)
            ->assertJsonPath('data.by_organization.0.total_absent', 1);
    }
}
