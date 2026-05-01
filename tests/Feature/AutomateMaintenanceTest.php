<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetReading;
use App\Models\MaintenanceSchedule;
use App\Models\Organization;
use App\Models\WorkOrder;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomateMaintenanceTest extends TestCase
{
    public function test_time_based_due_creates_work_order(): void
    {
        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-001',
            'name' => 'Pump A',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $schedule = MaintenanceSchedule::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'type' => 'TIME_BASED',
            'frequency' => 'MONTHLY',
            'interval_value' => 1,
            'next_due_date' => now()->subDay(),
            'priority' => 'MEDIUM',
            'is_active' => true,
        ]);

        $this->artisan('maintenance:process')->assertExitCode(0);

        $this->assertDatabaseHas('work_orders', [
            'asset_id' => $asset->id,
            'type' => 'PREVENTIVE',
            'status' => 'OPEN',
        ]);

        $schedule->refresh();
        $this->assertNotNull($schedule->last_completed_at);
        $this->assertTrue($schedule->next_due_date->isFuture());
    }

    public function test_meter_based_due_creates_work_order_and_updates_targets(): void
    {
        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-002',
            'name' => 'Generator B',
            'category' => 'Electrical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $schedule = MaintenanceSchedule::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'type' => 'METER_BASED',
            'frequency' => 'HOURS',
            'interval_value' => 100,
            'last_meter_reading' => 900,
            'target_meter_reading' => 950,
            'priority' => 'HIGH',
            'is_active' => true,
        ]);

        AssetReading::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'reading_value' => 1000,
            'reading_unit' => 'Hours',
            'recorded_at' => now(),
        ]);

        // Make it due
        $schedule->update(['last_meter_reading' => 960]);

        $this->artisan('maintenance:process')->assertExitCode(0);

        $this->assertDatabaseHas('work_orders', [
            'asset_id' => $asset->id,
            'type' => 'PREVENTIVE',
        ]);

        $schedule->refresh();
        $this->assertEquals(1000.0, (float) $schedule->last_meter_reading);
        $this->assertEquals(1100.0, (float) $schedule->target_meter_reading);
    }

    public function test_skip_if_open_work_order_exists(): void
    {
        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-003',
            'name' => 'Compressor C',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $schedule = MaintenanceSchedule::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'type' => 'TIME_BASED',
            'frequency' => 'WEEKLY',
            'interval_value' => 1,
            'next_due_date' => now()->subDay(),
            'priority' => 'LOW',
            'is_active' => true,
        ]);

        WorkOrder::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'organization_id' => $org->id,
            'type' => 'PREVENTIVE',
            'priority' => 'LOW',
            'status' => 'OPEN',
            'description' => 'Existing WO',
        ]);

        $this->artisan('maintenance:process')->assertExitCode(0);

        // Should not create a new WO
        $this->assertEquals(1, WorkOrder::where('asset_id', $asset->id)->count());
    }

    public function test_check_option_does_not_create_work_order(): void
    {
        $org = Organization::factory()->create();
        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'code' => 'AS-004',
            'name' => 'Fan D',
            'category' => 'Mechanical',
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        MaintenanceSchedule::create([
            'id' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'type' => 'TIME_BASED',
            'frequency' => 'DAILY',
            'interval_value' => 1,
            'next_due_date' => now()->subDay(),
            'priority' => 'MEDIUM',
            'is_active' => true,
        ]);

        $this->artisan('maintenance:process --check')->assertExitCode(0);

        $this->assertDatabaseMissing('work_orders', [
            'asset_id' => $asset->id,
        ]);
    }
}
