<?php

namespace App\Console\Commands;

use App\Models\AssetReading;
use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomateMaintenance extends Command
{
    protected $signature = 'maintenance:process 
                            {--check : Check for due schedules without creating WOs}
                            {--dry-run : Run without creating actual work orders}';

    protected $description = 'Process maintenance schedules and generate work orders automatically';

    public function handle()
    {
        $this->info('Starting maintenance automation...');
        $checkOnly = $this->option('check');
        $dryRun = $this->option('dry-run');

        $schedules = MaintenanceSchedule::where('is_active', true)
            ->whereHas('asset', function ($query) {
                $query->where('status', 'ACTIVE');
            })
            ->get();

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($schedules as $schedule) {
            if (! $schedule->isDue()) {
                $skippedCount++;

                continue;
            }

            if ($this->hasOpenWorkOrder($schedule->asset_id)) {
                $this->warn("Skipping: Asset {$schedule->asset->name} already has open WO");
                $skippedCount++;

                continue;
            }

            if ($checkOnly) {
                $this->info("Due: Schedule {$schedule->id} for asset {$schedule->asset->name}");
                $processedCount++;

                continue;
            }

            if (! $dryRun) {
                $this->createWorkOrderFromSchedule($schedule);
                $this->scheduleNextMaintenance($schedule);
            }

            $this->info("Created WO for asset: {$schedule->asset->name}");
            $processedCount++;
        }

        $this->info('Maintenance automation completed.');
        $this->info("Processed: {$processedCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }

    private function hasOpenWorkOrder(string $assetId): bool
    {
        return WorkOrder::where('asset_id', $assetId)
            ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
            ->exists();
    }

    private function createWorkOrderFromSchedule(MaintenanceSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $workOrder = WorkOrder::create([
                'asset_id' => $schedule->asset_id,
                'organization_id' => $schedule->asset->organization_id,
                'type' => 'PREVENTIVE',
                'priority' => $schedule->priority,
                'status' => 'OPEN',
                'description' => $schedule->instructions ?? "Scheduled maintenance: {$schedule->type} - {$schedule->frequency}",
                'assigned_to' => $schedule->assigned_to,
            ]);

            $schedule->update([
                'last_completed_at' => now(),
            ]);

            // Copy checklist items if available
            if ($schedule->checklist && is_array($schedule->checklist->checklist_items)) {
                foreach ($schedule->checklist->checklist_items as $item) {
                    WorkOrderChecklist::create([
                        'work_order_id' => $workOrder->id,
                        'item_name' => $item['name'] ?? 'Untitled Item',
                        'item_description' => $item['description'] ?? null,
                        'is_checked' => false,
                    ]);
                }
            }

            Log::info('Auto-generated WO', [
                'wo_id' => $workOrder->id,
                'schedule_id' => $schedule->id,
                'asset_id' => $schedule->asset_id,
            ]);
        });
    }

    private function scheduleNextMaintenance(MaintenanceSchedule $schedule): void
    {
        if ($schedule->type === 'TIME_BASED') {
            $schedule->scheduleNextDueDate();
        } elseif ($schedule->type === 'METER_BASED') {
            $lastReading = AssetReading::where('asset_id', $schedule->asset_id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if ($lastReading) {
                $schedule->update([
                    'last_meter_reading' => $lastReading->reading_value,
                    'target_meter_reading' => $lastReading->reading_value + $schedule->interval_value,
                ]);
            }
        }
    }
}
