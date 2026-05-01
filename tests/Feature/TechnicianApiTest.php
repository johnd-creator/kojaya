<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceChecklist;
use App\Models\MaintenanceSchedule;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TechnicianApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_creates_work_order_with_checklists(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-CHECKLIST',
            'name' => 'Machine With Checklist',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $checklist = MaintenanceChecklist::create([
            'id' => (string) Str::uuid(),
            'name' => 'Daily Inspection',
            'category' => 'PREVENTIVE',
            'organization_id' => $org->id,
            'checklist_items' => [
                ['name' => 'Check Oil Level', 'description' => 'Ensure between min/max'],
                ['name' => 'Check Vibration', 'description' => 'Ensure smooth operation'],
            ],
            'is_active' => true,
        ]);

        $schedule = MaintenanceSchedule::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'type' => 'TIME_BASED',
            'frequency' => 'DAILY',
            'interval_value' => 1,
            'next_due_date' => now()->subDay(),
            'priority' => 'HIGH',
            'maintenance_checklist_id' => $checklist->id,
            'is_active' => true,
            'assigned_to' => $user->id,
        ]);

        $this->artisan('maintenance:process')->assertExitCode(0);

        $workOrder = WorkOrder::where('asset_id', $asset->id)->first();
        $this->assertNotNull($workOrder);

        $this->assertDatabaseHas('work_order_checklists', [
            'work_order_id' => $workOrder->id,
            'item_name' => 'Check Oil Level',
            'is_checked' => false,
        ]);

        $this->assertCount(2, $workOrder->checklists);
    }

    public function test_technician_can_list_assigned_work_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-TECH',
            'name' => 'Tech Asset',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $myWO = WorkOrder::create([
            'asset_id' => $asset->id,
            'organization_id' => $org->id,
            'type' => 'PREVENTIVE',
            'status' => 'OPEN',
            'assigned_to' => $user->id,
        ]);

        $otherWO = WorkOrder::create([
            'asset_id' => $asset->id,
            'organization_id' => $org->id,
            'type' => 'PREVENTIVE',
            'status' => 'OPEN',
            'assigned_to' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/technician/work-orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $myWO->id])
            ->assertJsonMissing(['id' => $otherWO->id]);
    }

    public function test_technician_can_update_checklist_and_complete_wo(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-COMPLETE',
            'name' => 'Asset Complete',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $wo = WorkOrder::create([
            'asset_id' => $asset->id,
            'organization_id' => $org->id,
            'type' => 'PREVENTIVE',
            'status' => 'OPEN',
            'assigned_to' => $user->id,
        ]);

        $checklist = WorkOrderChecklist::create([
            'work_order_id' => $wo->id,
            'item_name' => 'Inspect Seals',
            'is_checked' => false,
        ]);

        // Start WO
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/technician/work-orders/{$wo->id}/start")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        // Try complete (should fail)
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/technician/work-orders/{$wo->id}/complete")
            ->assertStatus(422);

        // Update checklist
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/technician/work-orders/{$wo->id}/checklists/{$checklist->id}", [
                'is_checked' => true,
                'notes' => 'Seals look good',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.is_checked', true);

        // Complete WO (should succeed)
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/technician/work-orders/{$wo->id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'COMPLETED');
    }
}
