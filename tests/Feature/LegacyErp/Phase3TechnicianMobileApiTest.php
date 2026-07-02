<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklist;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3TechnicianMobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_work_order_list_supports_filters_and_pagination(): void
    {
        [$user, $organization, $asset] = $this->technicianContext();
        $matching = WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $user->id,
            'status' => 'OPEN',
            'priority' => 'HIGH',
            'scheduled_date' => today()->toDateString(),
        ]);
        WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $user->id,
            'status' => 'IN_PROGRESS',
            'priority' => 'LOW',
            'scheduled_date' => today()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs($user, ['work-orders:read']);

        $this->getJson('/api/technician/work-orders?status=OPEN&priority=HIGH&scheduled_date='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_technician_can_upload_evidence_record_parts_complete_with_gps_and_read_timeline(): void
    {
        Storage::fake('public');
        [$user, $organization, $asset] = $this->technicianContext();
        $workOrder = WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $user->id,
            'status' => 'OPEN',
        ]);
        $checklist = WorkOrderChecklist::factory()->create([
            'work_order_id' => $workOrder->id,
            'is_checked' => false,
        ]);
        $sparePart = SparePart::factory()->create(['organization_id' => $organization->id]);
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user, ['work-orders:read', 'work-orders:write']);

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/start", [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 12,
        ])->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        $attachmentPath = $this->postJson("/api/technician/work-orders/{$workOrder->id}/attachments", [
            'type' => 'BEFORE',
            'file' => UploadedFile::fake()->image('before.jpg'),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'notes' => 'Kondisi awal',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'BEFORE')
            ->json('data.path');
        Storage::disk('public')->assertExists($attachmentPath);

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/parts", [
            'spare_part_id' => $sparePart->id,
            'warehouse_id' => $warehouse->id,
            'quantity_used' => 2,
            'notes' => 'Ganti seal',
        ])->assertCreated()
            ->assertJsonPath('data.spare_part_id', $sparePart->id);

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/checklists/{$checklist->id}", [
            'is_checked' => true,
            'notes' => 'Selesai',
        ])->assertOk();

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/complete", [
            'latitude' => -6.21,
            'longitude' => 106.81,
            'accuracy' => 10,
            'notes' => 'Pekerjaan selesai',
        ])->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.completion_notes', 'Pekerjaan selesai');

        $this->getJson("/api/technician/work-orders/{$workOrder->id}/timeline")
            ->assertOk()
            ->assertJsonFragment(['event_type' => 'attachment_uploaded'])
            ->assertJsonFragment(['event_type' => 'part_recorded'])
            ->assertJsonFragment(['event_type' => 'completed']);
    }

    public function test_offline_sync_uses_idempotency_key_to_prevent_duplicate_parts(): void
    {
        [$user, $organization, $asset] = $this->technicianContext();
        $workOrder = WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $user->id,
            'status' => 'IN_PROGRESS',
        ]);
        $checklist = WorkOrderChecklist::factory()->create([
            'work_order_id' => $workOrder->id,
            'is_checked' => false,
        ]);
        $sparePart = SparePart::factory()->create(['organization_id' => $organization->id]);
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user, ['work-orders:write']);

        $payload = [
            'idempotency_key' => 'offline-001',
            'checklists' => [
                ['id' => $checklist->id, 'is_checked' => true, 'notes' => 'Checked offline'],
            ],
            'parts' => [
                [
                    'spare_part_id' => $sparePart->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity_used' => 1,
                    'notes' => 'Offline part',
                ],
            ],
        ];

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/sync", $payload)
            ->assertOk()
            ->assertJsonPath('data.idempotency_key', 'offline-001')
            ->assertJsonCount(1, 'data.created_parts');

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/sync", $payload)
            ->assertOk()
            ->assertJsonPath('data.idempotency_key', 'offline-001')
            ->assertJsonCount(1, 'data.created_parts');

        $this->assertSame(1, WorkOrderPart::query()->where('work_order_id', $workOrder->id)->count());
    }

    public function test_escalation_and_supervisor_reopen_are_tracked_in_timeline(): void
    {
        [$user, $organization, $asset] = $this->technicianContext();
        $supervisor = User::factory()->create(['organization_id' => $organization->id]);
        $workOrder = WorkOrder::factory()->completed()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $user->id,
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user, ['work-orders:write']);

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/escalate", [
            'type' => 'NEED_SUPERVISOR',
            'reason' => 'Butuh review ulang',
            'reassignment_requested_to' => $supervisor->id,
        ])->assertOk()
            ->assertJsonPath('data.escalation_type', 'NEED_SUPERVISOR');

        Sanctum::actingAs($supervisor, ['work-orders:read', 'work-orders:review']);

        $this->postJson("/api/technician/work-orders/{$workOrder->id}/reopen", [
            'reason' => 'Evidence kurang jelas',
        ])->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS')
            ->assertJsonPath('data.reopen_reason', 'Evidence kurang jelas');

        $this->getJson("/api/technician/work-orders/{$workOrder->id}/timeline")
            ->assertOk()
            ->assertJsonFragment(['event_type' => 'escalated'])
            ->assertJsonFragment(['event_type' => 'reopened']);
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Organization, 2: \App\Models\Asset}
     */
    private function technicianContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $asset = Asset::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-'.Str::upper(Str::random(6)),
            'name' => 'Field Asset',
            'category' => 'Mechanical',
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
        ]);

        return [$user, $organization, $asset];
    }
}
