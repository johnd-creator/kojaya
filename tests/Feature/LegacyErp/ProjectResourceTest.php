<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectAssetAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_store_generates_uuid_without_controller_assigning_id(): void
    {
        $organization = Organization::factory()->create();
        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'project_code' => 'PRJ-UUID-001',
            'name' => 'UUID Regression Project',
            'description' => 'Verify Project UUID auto-generation.',
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'budget' => 1000000,
            'status' => 'PLANNING',
            'notes' => 'Regression test',
        ]);

        $response->assertRedirect(route('projects.index'));

        $project = Project::query()->where('project_code', 'PRJ-UUID-001')->firstOrFail();

        $this->assertNotNull($project->id);
        $this->assertTrue(Str::isUuid($project->id));
    }

    public function test_can_allocate_asset_to_project(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Project Res',
            'project_code' => 'PROJ-RES',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000,
            'status' => 'ONGOING',
        ]);

        $asset = Asset::create([
            'organization_id' => $org->id,
            'name' => 'Excavator',
            'code' => 'EXC-01',
            'category' => 'HEAVY_EQUIPMENT',
            'status' => 'ACTIVE',
            'purchase_date' => now(),
            'purchase_cost' => 500000,
            'purchase_price' => 500000,
        ]);

        $response = $this->actingAs($user)->post(route('projects.resources.store-asset', $project), [
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'planned',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_asset_allocations', [
            'project_id' => $project->id,
            'asset_id' => $asset->id,
            'status' => 'planned',
        ]);
    }

    public function test_prevents_conflicting_asset_allocation(): void
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

        $asset = Asset::create([
            'organization_id' => $org->id,
            'name' => 'Excavator',
            'code' => 'EXC-01',
            'category' => 'HEAVY_EQUIPMENT',
            'status' => 'ACTIVE',
            'purchase_date' => now(),
            'purchase_cost' => 500000,
            'purchase_price' => 500000,
        ]);

        // Allocate to Project A for Jan 1 - Jan 31
        ProjectAssetAllocation::create([
            'project_id' => $projectA->id,
            'asset_id' => $asset->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'mobilized',
        ]);

        // Try allocate to Project B for Jan 15 - Feb 15 (Overlap)
        $response = $this->actingAs($user)->post(route('projects.resources.store-asset', $projectB), [
            'asset_id' => $asset->id,
            'start_date' => '2026-01-15',
            'end_date' => '2026-02-15',
            'status' => 'planned',
        ]);

        $response->assertSessionHasErrors(['asset_id']);
    }
}
