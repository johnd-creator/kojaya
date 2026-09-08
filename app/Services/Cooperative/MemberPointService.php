<?php

namespace App\Services\Cooperative;

use App\Models\PosMemberPoint;
use App\Models\PosTransaction;
use Illuminate\Auth\Access\AuthorizationException;

class MemberPointService
{
    private const POS_POINT_RUPIAH_RATE = 1000;

    public function __construct(private readonly PointService $pointService) {}

    public static function pointsForProfit(float $profit): int
    {
        if ($profit <= 0) {
            return 0;
        }

        return max((int) floor($profit / self::POS_POINT_RUPIAH_RATE), 1);
    }

    public function postFromTransaction(PosTransaction $transaction): ?PosMemberPoint
    {
        if (! $transaction->cooperative_member_id) {
            return null;
        }

        $transaction->loadMissing('member');

        if (! $transaction->member) {
            return null;
        }

        if (blank($transaction->organization_id) || blank($transaction->member->organization_id)) {
            throw new AuthorizationException('Transaction or member is missing a valid organization.');
        }

        if ((string) $transaction->organization_id !== (string) $transaction->member->organization_id) {
            throw new AuthorizationException('Transaction organization does not match member organization.');
        }

        $profit = (float) $transaction->gross_profit;
        $points = self::pointsForProfit($profit);

        if ($points === 0) {
            return null;
        }

        $point = PosMemberPoint::query()->firstOrCreate(
            ['pos_transaction_id' => $transaction->id],
            [
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'year' => (int) $transaction->sold_at->format('Y'),
                'profit_amount' => $profit,
                'points' => $points,
                'posted_at' => $transaction->sold_at->toDateString(),
            ],
        );

        if ((int) $point->points !== $points || (float) $point->profit_amount !== $profit) {
            $point->forceFill([
                'profit_amount' => $profit,
                'points' => $points,
                'posted_at' => $transaction->sold_at->toDateString(),
            ])->save();
        }

        $point->loadMissing('member', 'transaction');

        if ($point->member) {
            $this->pointService->syncPosPoints($point->member);
        }

        return $point;
    }
}
