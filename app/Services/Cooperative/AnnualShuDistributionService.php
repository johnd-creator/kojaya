<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativeShuPeriod;
use App\Models\PosMemberPoint;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnualShuDistributionService
{
    /**
     * @return array<string, mixed>
     */
    public function preview(int $year, float $cooperativePool = 0, ?float $posProfitPool = null): array
    {
        $posProfitPool ??= (float) PosTransaction::query()
            ->whereYear('sold_at', $year)
            ->sum('gross_profit');

        $members = CooperativeMember::query()
            ->where('status', 'ACTIVE')
            ->whereDate('joined_at', '<=', CarbonImmutable::create($year, 12, 31)->toDateString())
            ->orderBy('member_no')
            ->get();

        $posPoints = PosMemberPoint::query()
            ->selectRaw('cooperative_member_id, sum(points) as points')
            ->where('year', $year)
            ->groupBy('cooperative_member_id')
            ->pluck('points', 'cooperative_member_id');

        $allocations = [];
        $totalMembershipScore = 0;
        $totalDuesScore = 0;
        $totalShuScore = 0;
        $totalPosPoints = (int) $posPoints->sum();

        foreach ($members as $member) {
            $membershipScore = $this->membershipMonths($member, $year);
            $duesScore = $this->paidMandatoryDuesMonths($member, $year);
            $shuScore = $membershipScore + $duesScore;
            $memberPosPoints = (int) ($posPoints[$member->id] ?? 0);

            $totalMembershipScore += $membershipScore;
            $totalDuesScore += $duesScore;
            $totalShuScore += $shuScore;

            $allocations[] = [
                'member' => $member,
                'membership_score' => $membershipScore,
                'dues_score' => $duesScore,
                'shu_score' => $shuScore,
                'pos_points' => $memberPosPoints,
            ];
        }

        $allocations = array_map(function (array $allocation) use ($cooperativePool, $posProfitPool, $totalShuScore, $totalPosPoints): array {
            $cooperativeShuAmount = $totalShuScore > 0
                ? round(((float) $allocation['shu_score'] / $totalShuScore) * $cooperativePool, 2)
                : 0;
            $posShuAmount = $totalPosPoints > 0
                ? round(((int) $allocation['pos_points'] / $totalPosPoints) * $posProfitPool, 2)
                : 0;

            return [
                ...$allocation,
                'cooperative_shu_amount' => $cooperativeShuAmount,
                'pos_shu_amount' => $posShuAmount,
                'total_amount' => $cooperativeShuAmount + $posShuAmount,
            ];
        }, $allocations);

        return [
            'year' => $year,
            'cooperative_pool' => $cooperativePool,
            'pos_profit_pool' => $posProfitPool,
            'total_membership_score' => $totalMembershipScore,
            'total_dues_score' => $totalDuesScore,
            'total_shu_score' => $totalShuScore,
            'total_pos_points' => $totalPosPoints,
            'allocations' => $allocations,
        ];
    }

    public function close(int $year, float $cooperativePool = 0, ?float $posProfitPool = null, ?User $user = null): CooperativeShuPeriod
    {
        return DB::transaction(function () use ($year, $cooperativePool, $posProfitPool, $user): CooperativeShuPeriod {
            $period = CooperativeShuPeriod::query()->lockForUpdate()->firstOrNew(['year' => $year]);

            if ($period->exists && $period->status === 'CLOSED') {
                throw ValidationException::withMessages([
                    'year' => 'This annual SHU period has already been closed.',
                ]);
            }

            $preview = $this->preview($year, $cooperativePool, $posProfitPool);

            $period->fill([
                'cooperative_pool' => $preview['cooperative_pool'],
                'pos_profit_pool' => $preview['pos_profit_pool'],
                'total_membership_score' => $preview['total_membership_score'],
                'total_dues_score' => $preview['total_dues_score'],
                'total_shu_score' => $preview['total_shu_score'],
                'total_pos_points' => $preview['total_pos_points'],
                'status' => 'CLOSED',
                'closed_at' => now(),
                'closed_by' => $user?->id,
            ])->save();

            $period->allocations()->delete();

            foreach ($preview['allocations'] as $allocation) {
                $period->allocations()->create([
                    'cooperative_member_id' => $allocation['member']->id,
                    'membership_score' => $allocation['membership_score'],
                    'dues_score' => $allocation['dues_score'],
                    'shu_score' => $allocation['shu_score'],
                    'cooperative_shu_amount' => $allocation['cooperative_shu_amount'],
                    'pos_points' => $allocation['pos_points'],
                    'pos_shu_amount' => $allocation['pos_shu_amount'],
                    'total_amount' => $allocation['total_amount'],
                ]);
            }

            return $period->load('allocations.member');
        });
    }

    private function membershipMonths(CooperativeMember $member, int $year): int
    {
        if (! $member->joined_at) {
            return 0;
        }

        $start = CarbonImmutable::parse($member->joined_at)->max(CarbonImmutable::create($year, 1, 1));
        $end = CarbonImmutable::create($year, 12, 31);

        if ($start->greaterThan($end)) {
            return 0;
        }

        return (($end->year - $start->year) * 12) + ($end->month - $start->month) + 1;
    }

    private function paidMandatoryDuesMonths(CooperativeMember $member, int $year): int
    {
        return CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->where('status', 'PAID')
            ->where('period', 'like', $year.'-%')
            ->whereHas('contributionType', function ($query): void {
                $query->where('code', 'WAJIB')
                    ->orWhere('category', 'WAJIB');
            })
            ->distinct('period')
            ->count('period');
    }
}
