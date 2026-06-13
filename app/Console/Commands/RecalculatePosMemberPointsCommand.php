<?php

namespace App\Console\Commands;

use App\Models\CooperativeMember;
use App\Models\PosMemberPoint;
use App\Services\Cooperative\MemberPointService;
use App\Services\Cooperative\PointService;
use Illuminate\Console\Command;

class RecalculatePosMemberPointsCommand extends Command
{
    protected $signature = 'cooperative:recalculate-pos-points {--member= : Cooperative member ID to recalculate}';

    protected $description = 'Recalculate POS member points using the current reward point rate.';

    public function handle(PointService $pointService): int
    {
        $query = PosMemberPoint::query()->with('member');

        if ($this->option('member')) {
            $query->where('cooperative_member_id', $this->option('member'));
        }

        $updated = 0;
        $memberIds = [];

        $query->orderBy('id')->chunkById(100, function ($points) use (&$memberIds, &$updated): void {
            foreach ($points as $point) {
                $expectedPoints = MemberPointService::pointsForProfit((float) $point->profit_amount);

                if ((int) $point->points !== $expectedPoints) {
                    $point->forceFill(['points' => $expectedPoints])->save();
                    $updated++;
                }

                $memberIds[] = $point->cooperative_member_id;
            }
        });

        CooperativeMember::query()
            ->whereIn('id', array_values(array_unique($memberIds)))
            ->orderBy('id')
            ->get()
            ->each(function (CooperativeMember $member) use ($pointService): void {
                $pointService->syncPosPoints($member);
                $pointService->rebuildBalances($member);
            });

        $this->info("Recalculated {$updated} POS point rows.");
        $this->info('Rebuilt point balances for '.count(array_unique($memberIds)).' members.');

        return self::SUCCESS;
    }
}
