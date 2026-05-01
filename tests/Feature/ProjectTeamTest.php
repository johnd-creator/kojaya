<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_assign_team_member(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project Team Test',
            'project_code' => 'PROJ-TEAM',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('team.store', $project), [
            'employee_id' => $employee->id,
            'role' => 'Engineer',
            'start_date' => now()->toDateString(),
            'daily_rate_cost' => 100000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_team', [
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role' => 'Engineer',
        ]);
    }

    public function test_prevents_conflicting_team_assignment(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $projectA = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project A',
            'project_code' => 'PROJ-A',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);
        $projectB = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project B',
            'project_code' => 'PROJ-B',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        // Assign to Project A
        ProjectTeam::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'project_id' => $projectA->id,
            'employee_id' => $employee->id,
            'role' => 'Engineer',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'daily_rate_cost' => 100000,
        ]);

        // Try assign to Project B (Overlap)
        $response = $this->actingAs($user)->post(route('team.store', $projectB), [
            'employee_id' => $employee->id,
            'role' => 'Engineer',
            'start_date' => '2026-01-15',
            'end_date' => '2026-02-15',
            'daily_rate_cost' => 100000,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_can_bulk_assign_team_members(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project Bulk',
            'project_code' => 'PROJ-BULK',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $emp1 = Employee::factory()->create(['organization_id' => $org->id]);
        $emp2 = Employee::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('team.bulk-assign', $project), [
            'employee_ids' => [$emp1->id, $emp2->id],
            'role' => 'Worker',
            'start_date' => now()->toDateString(),
            'daily_rate_cost' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('project_team', 2);
    }
}
