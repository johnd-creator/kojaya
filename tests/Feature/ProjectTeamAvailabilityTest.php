<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTeam;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectTeamAvailabilityTest extends TestCase
{
    public function test_availability_returns_conflicts_for_overlapping_assignment_in_other_project()
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $employee = Employee::factory()->create();

        $projectA = Project::create([
            'id' => (string) Str::uuid(),
            'project_code' => 'PROJ-A',
            'name' => 'Project A',
            'organization_id' => $employee->organization_id,
            'client_id' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'budget' => 1000000,
            'actual_cost' => 0,
            'status' => 'ONGOING',
            'progress_percentage' => 0,
            'notes' => null,
        ]);

        $projectB = Project::create([
            'id' => (string) Str::uuid(),
            'project_code' => 'PROJ-B',
            'name' => 'Project B',
            'organization_id' => $employee->organization_id,
            'client_id' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'budget' => 1000000,
            'actual_cost' => 0,
            'status' => 'ONGOING',
            'progress_percentage' => 0,
            'notes' => null,
        ]);

        ProjectTeam::create([
            'id' => (string) Str::uuid(),
            'project_id' => $projectA->id,
            'employee_id' => $employee->id,
            'role' => 'Worker',
            'start_date' => '2026-02-01',
            'end_date' => null,
            'daily_rate_cost' => 100000,
        ]);

        $url = route('projects.team.availability', $projectB).'?'.http_build_query([
            'employee_id' => $employee->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJson([
            'available' => false,
        ]);
        $response->assertJsonCount(1, 'conflicts');
        $response->assertJsonPath('conflicts.0.project_id', $projectA->id);
    }

    public function test_availability_ignores_assignments_in_same_project()
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $employee = Employee::factory()->create();

        $project = Project::create([
            'id' => (string) Str::uuid(),
            'project_code' => 'PROJ-A',
            'name' => 'Project A',
            'organization_id' => $employee->organization_id,
            'client_id' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'budget' => 1000000,
            'actual_cost' => 0,
            'status' => 'ONGOING',
            'progress_percentage' => 0,
            'notes' => null,
        ]);

        ProjectTeam::create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role' => 'Worker',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-10',
            'daily_rate_cost' => 100000,
        ]);

        $url = route('projects.team.availability', $project).'?'.http_build_query([
            'employee_id' => $employee->id,
            'start_date' => '2026-02-05',
            'end_date' => '2026-02-07',
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJson([
            'available' => true,
            'conflicts' => [],
        ]);
    }
}
