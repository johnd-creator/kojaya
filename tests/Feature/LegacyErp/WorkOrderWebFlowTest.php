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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkOrderWebFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_work_order_index(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        WorkOrder::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'OPEN',
        ]);
        WorkOrder::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'COMPLETED',
        ]);
        WorkOrder::factory()->create([
            'organization_id' => $otherOrganization->id,
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.index', [
                'organization_id' => $organization->id,
                'status' => 'OPEN',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Index')
                ->has('workOrders', 1)
                ->where('workOrders.0.status', 'OPEN')
            );
    }

    public function test_user_can_view_work_order_create_page(): void
    {
        $user = User::factory()->create();
        Asset::factory()->count(2)->create();
        Organization::factory()->count(2)->create();
        User::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('work-orders.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Create')
                ->has('assets', 2)
                ->has('organizations')
                ->has('users')
            );
    }

    public function test_user_can_store_work_order(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $technician = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'asset_id' => $asset->id,
                'organization_id' => $organization->id,
                'type' => 'CORRECTIVE',
                'priority' => 'HIGH',
                'description' => 'Perbaikan unit pendingin',
                'assigned_to' => $technician->id,
            ])
            ->assertRedirect(route('work-orders.index'));

        $this->assertDatabaseHas('work_orders', [
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $technician->id,
            'status' => 'OPEN',
        ]);
    }

    public function test_user_can_view_work_order_detail_with_parts_and_checklists(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);
        $sparePart = SparePart::factory()->create(['organization_id' => $organization->id]);
        $assignedUser = User::factory()->create(['organization_id' => $organization->id]);
        $workOrder = WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
            'assigned_to' => $assignedUser->id,
        ]);
        WorkOrderPart::factory()->create([
            'work_order_id' => $workOrder->id,
            'spare_part_id' => $sparePart->id,
            'warehouse_id' => $warehouse->id,
        ]);
        WorkOrderChecklist::factory()->create([
            'work_order_id' => $workOrder->id,
            'checked_by' => $assignedUser->id,
            'is_checked' => true,
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.show', $workOrder->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkOrders/Show')
                ->where('workOrder.id', $workOrder->id)
                ->has('workOrder.parts', 1)
                ->has('workOrder.checklists', 1)
            );
    }
}
