<?php

namespace App\Services\Cooperative;

use App\Models\PosMemberPoint;
use App\Models\PosTransaction;

class MemberPointService
{
    public function postFromTransaction(PosTransaction $transaction): ?PosMemberPoint
    {
        if (! $transaction->cooperative_member_id) {
            return null;
        }

        $profit = (float) $transaction->gross_profit;
        $points = max((int) floor($profit), 0);

        if ($points === 0) {
            return null;
        }

        return PosMemberPoint::query()->firstOrCreate(
            ['pos_transaction_id' => $transaction->id],
            [
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'year' => (int) $transaction->sold_at->format('Y'),
                'profit_amount' => $profit,
                'points' => $points,
                'posted_at' => $transaction->sold_at->toDateString(),
            ],
        );
    }
}
