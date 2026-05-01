<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMilestoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_project_milestone(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project A',
            'project_code' => 'PROJ-A',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $response = $this->actingAs($user)->post(route('milestones.store', $project), [
            'name' => 'Phase 1',
            'due_date' => now()->addMonth()->toDateString(),
            'progress_percentage' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'Phase 1',
            'status' => 'PENDING',
        ]);
    }

    public function test_can_update_milestone_progress(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project B',
            'project_code' => 'PROJ-B',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $milestone = ProjectMilestone::create([
            'project_id' => $project->id,
            'name' => 'Phase 1',
            'due_date' => now()->addMonth(),
            'progress_percentage' => 0,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->patch(route('milestones.update-progress', ['project' => $project, 'milestone' => $milestone]), [
            'progress_percentage' => 50,
        ]);

        $response->assertRedirect();
        $milestone->refresh();
        $this->assertEquals(50, $milestone->progress_percentage);
        $this->assertEquals('IN_PROGRESS', $milestone->status);
    }
}
