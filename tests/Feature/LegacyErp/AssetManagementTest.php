<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_filtered_asset_index(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        Asset::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
        ]);
        Asset::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'INACTIVE',
        ]);
        Asset::factory()->create([
            'organization_id' => $otherOrganization->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->get(route('assets.index', [
                'organization_id' => $organization->id,
                'status' => 'ACTIVE',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Index')
                ->has('assets', 1)
                ->where('filters.organization_id', $organization->id)
                ->where('filters.status', 'ACTIVE')
            );
    }

    public function test_user_can_create_and_update_asset(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->from(route('assets.create'))
            ->post(route('assets.store'), [
                'code' => 'AST-1001',
                'name' => 'Excavator Mini',
                'category' => 'MACHINE',
                'organization_id' => $organization->id,
                'status' => 'ACTIVE',
                'purchase_date' => now()->toDateString(),
                'serial_number' => 'SN-1001',
            ])
            ->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('code', 'AST-1001')->first();

        $this->assertNotNull($asset);

        $this->actingAs($user)
            ->from(route('assets.edit', $asset->id))
            ->put(route('assets.update', $asset->id), [
                'code' => 'AST-1001',
                'name' => 'Excavator Mini Updated',
                'category' => 'HEAVY_EQUIPMENT',
                'organization_id' => $organization->id,
                'status' => 'UNDER_MAINTENANCE',
                'purchase_date' => now()->toDateString(),
                'serial_number' => 'SN-1001-REV',
            ])
            ->assertRedirect(route('assets.index'));

        $asset->refresh();

        $this->assertSame('Excavator Mini Updated', $asset->name);
        $this->assertSame('UNDER_MAINTENANCE', $asset->status);
        $this->assertSame('SN-1001-REV', $asset->serial_number);
    }

    public function test_asset_with_existing_work_orders_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        WorkOrder::factory()->create([
            'asset_id' => $asset->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->from(route('assets.index'))
            ->delete(route('assets.destroy', $asset->id))
            ->assertRedirect(route('assets.index'))
            ->assertSessionHas('error', 'Cannot delete asset with existing work orders.');

        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    public function test_asset_without_work_orders_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($user)
            ->delete(route('assets.destroy', $asset->id))
            ->assertRedirect(route('assets.index'));

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }
}
