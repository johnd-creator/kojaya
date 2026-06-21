<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\Reward;
use App\Models\RewardRedemption;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function syncPosPoints(CooperativeMember $member): void
    {
        $points = $member->posMemberPoints()
            ->with('transaction')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        $shouldRebuildBalances = false;

        foreach ($points as $point) {
            $expectedPoints = MemberPointService::pointsForProfit((float) $point->profit_amount);

            if ((int) $point->points !== $expectedPoints) {
                $point->forceFill(['points' => $expectedPoints])->save();
            }

            $existingTransaction = PointTransaction::query()
                ->where('cooperative_member_id', $member->id)
                ->where('transaction_type', 'EARNED')
                ->where('source_type', PosMemberPoint::class)
                ->where('source_id', (string) $point->id)
                ->first();

            if ($existingTransaction) {
                if ((int) $existingTransaction->points !== $expectedPoints) {
                    $existingTransaction->forceFill([
                        'points' => $expectedPoints,
                        'description' => 'Poin dari transaksi POS koperasi',
                        'posted_at' => Carbon::parse($point->posted_at)->toDateString(),
                        'metadata' => [
                            'profit_amount' => (float) $point->profit_amount,
                            'point_rate' => '1 poin per Rp1.000 laba kotor POS',
                        ],
                    ])->save();

                    $shouldRebuildBalances = true;
                }

                continue;
            }

            $this->recordTransaction(
                member: $member,
                transactionType: 'EARNED',
                points: $expectedPoints,
                description: 'Poin dari transaksi POS koperasi',
                postedAt: Carbon::parse($point->posted_at),
                sourceType: PosMemberPoint::class,
                sourceId: (string) $point->id,
                referenceNumber: optional($point->transaction)->transaction_number,
                metadata: [
                    'profit_amount' => (float) $point->profit_amount,
                    'point_rate' => '1 poin per Rp1.000 laba kotor POS',
                ],
            );
        }

        if ($shouldRebuildBalances) {
            $this->rebuildBalances($member);
        }
    }

    public function balanceSummary(CooperativeMember $member): array
    {
        $this->syncPosPoints($member);

        $latestBalance = (int) $member->pointTransactions()
            ->latest('posted_at')
            ->latest('created_at')
            ->value('balance_after');

        $pointsEarned = (int) $member->pointTransactions()
            ->where('transaction_type', 'EARNED')
            ->sum(DB::raw('ABS(points)'));

        $pointsRedeemed = (int) $member->pointTransactions()
            ->whereIn('transaction_type', ['REDEEMED', 'EXPIRED'])
            ->sum(DB::raw('ABS(points)'));

        $tiers = [
            'BRONZE' => 0,
            'SILVER' => 1000,
            'GOLD' => 2500,
            'PLATINUM' => 5000,
        ];

        $memberTier = 'BRONZE';
        $nextTier = null;
        $pointsToNextTier = 0;

        foreach ($tiers as $tier => $threshold) {
            if ($pointsEarned >= $threshold) {
                $memberTier = $tier;

                continue;
            }

            $nextTier = $tier;
            $pointsToNextTier = $threshold - $pointsEarned;

            break;
        }

        return [
            'total_points' => $latestBalance,
            'points_earned' => $pointsEarned,
            'points_redeemed' => $pointsRedeemed,
            'member_tier' => $memberTier,
            'next_tier' => $nextTier,
            'points_to_next_tier' => $pointsToNextTier,
        ];
    }

    public function historyQuery(CooperativeMember $member): Builder
    {
        $this->syncPosPoints($member);

        return PointTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at');
    }

    public function redeem(
        CooperativeMember $member,
        Reward $reward,
        int $quantity,
        ?string $deliveryAddress = null
    ): RewardRedemption {
        $this->syncPosPoints($member);

        return DB::transaction(function () use ($deliveryAddress, $member, $quantity, $reward): RewardRedemption {
            $lockedReward = Reward::query()->lockForUpdate()->findOrFail($reward->id);
            $summary = $this->balanceSummary($member);
            $pointsRequired = (int) $lockedReward->points_required * $quantity;

            if (! $lockedReward->is_active) {
                abort(422, 'Reward is not active.');
            }

            if ($lockedReward->valid_until && $lockedReward->valid_until->isPast()) {
                abort(422, 'Reward is no longer valid.');
            }

            if ($lockedReward->stock !== null && $lockedReward->stock < $quantity) {
                abort(422, 'Reward stock is insufficient.');
            }

            if ($summary['total_points'] < $pointsRequired) {
                abort(422, 'Insufficient points balance.');
            }

            if ($lockedReward->stock !== null) {
                $lockedReward->decrement('stock', $quantity);
            }

            $redemption = RewardRedemption::query()->create([
                'reward_id' => $lockedReward->id,
                'cooperative_member_id' => $member->id,
                'point_transaction_id' => null,
                'quantity' => $quantity,
                'points_used' => $pointsRequired,
                'delivery_address' => $deliveryAddress,
                'status' => 'PENDING',
                'redeemed_at' => now(),
            ]);

            $transaction = $this->recordTransaction(
                member: $member,
                transactionType: 'REDEEMED',
                points: $pointsRequired * -1,
                description: 'Penukaran reward: '.$lockedReward->name,
                postedAt: now(),
                sourceType: RewardRedemption::class,
                sourceId: $redemption->id,
                referenceNumber: null,
                metadata: [
                    'reward_id' => $lockedReward->id,
                    'reward_name' => $lockedReward->name,
                    'quantity' => $quantity,
                ],
            );

            $redemption->forceFill(['point_transaction_id' => $transaction->id])->save();

            return $redemption->refresh();
        });
    }

    public function updateRedemptionStatus(
        RewardRedemption $redemption,
        string $status,
        ?string $notes = null
    ): RewardRedemption {
        return DB::transaction(function () use ($notes, $redemption, $status): RewardRedemption {
            $lockedRedemption = RewardRedemption::query()
                ->with(['member', 'reward'])
                ->lockForUpdate()
                ->findOrFail($redemption->id);

            if ($lockedRedemption->status === 'DELIVERED' && $status === 'CANCELLED') {
                abort(422, 'Delivered redemptions cannot be cancelled.');
            }

            if ($lockedRedemption->status === 'CANCELLED' && $status !== 'CANCELLED') {
                abort(422, 'Cancelled redemptions cannot be reopened.');
            }

            if ($status === 'CANCELLED' && $lockedRedemption->status !== 'CANCELLED') {
                $this->refundRedemption($lockedRedemption);
            }

            $lockedRedemption->forceFill([
                'status' => $status,
                'notes' => $notes ?? $lockedRedemption->notes,
                'processed_at' => in_array($status, ['SHIPPED', 'DELIVERED', 'CANCELLED'], true)
                    ? now()
                    : $lockedRedemption->processed_at,
            ])->save();

            return $lockedRedemption->refresh();
        });
    }

    public function expire(CooperativeMember $member, Carbon $asOf): int
    {
        $this->syncPosPoints($member);

        $expired = 0;
        $candidates = $member->pointTransactions()
            ->where('transaction_type', 'EARNED')
            ->whereDate('expires_at', '<=', $asOf->toDateString())
            ->get();

        foreach ($candidates as $candidate) {
            $alreadyExpired = $member->pointTransactions()
                ->where('transaction_type', 'EXPIRED')
                ->where('source_type', PointTransaction::class)
                ->where('source_id', $candidate->id)
                ->exists();

            if ($alreadyExpired) {
                continue;
            }

            $this->recordTransaction(
                member: $member,
                transactionType: 'EXPIRED',
                points: abs((int) $candidate->points) * -1,
                description: 'Poin kedaluwarsa',
                postedAt: $asOf,
                sourceType: PointTransaction::class,
                sourceId: $candidate->id,
                referenceNumber: $candidate->reference_number,
                metadata: [
                    'expired_transaction_id' => $candidate->id,
                ],
            );

            $expired++;
        }

        return $expired;
    }

    public function recordTransaction(
        CooperativeMember $member,
        string $transactionType,
        int $points,
        string $description,
        CarbonInterface $postedAt,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $referenceNumber = null,
        ?CarbonInterface $expiresAt = null,
        ?array $metadata = null
    ): PointTransaction {
        $latestBalance = (int) PointTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->latest('posted_at')
            ->latest('created_at')
            ->value('balance_after');

        $nextBalance = max($latestBalance + $points, 0);

        return PointTransaction::query()->create([
            'cooperative_member_id' => $member->id,
            'transaction_type' => $transactionType,
            'points' => $points,
            'balance_before' => $latestBalance,
            'balance_after' => $nextBalance,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference_number' => $referenceNumber,
            'description' => $description,
            'posted_at' => $postedAt->toDateString(),
            'expires_at' => $expiresAt?->toDateString(),
            'metadata' => $metadata,
        ]);
    }

    public function rebuildBalances(CooperativeMember $member): void
    {
        $balance = 0;

        $member->pointTransactions()
            ->orderBy('posted_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(function (PointTransaction $transaction) use (&$balance): void {
                $before = $balance;
                $balance = max($balance + (int) $transaction->points, 0);

                $transaction->forceFill([
                    'balance_before' => $before,
                    'balance_after' => $balance,
                ])->save();
            });
    }

    private function refundRedemption(RewardRedemption $redemption): void
    {
        $alreadyRefunded = PointTransaction::query()
            ->where('transaction_type', 'REFUNDED')
            ->where('source_type', RewardRedemption::class)
            ->where('source_id', $redemption->id)
            ->exists();

        if ($alreadyRefunded) {
            return;
        }

        $this->recordTransaction(
            member: $redemption->member,
            transactionType: 'REFUNDED',
            points: (int) $redemption->points_used,
            description: 'Pengembalian poin redemption: '.$redemption->reward->name,
            postedAt: now(),
            sourceType: RewardRedemption::class,
            sourceId: $redemption->id,
            referenceNumber: $redemption->id,
            metadata: [
                'reward_id' => $redemption->reward_id,
                'reward_name' => $redemption->reward->name,
                'quantity' => $redemption->quantity,
            ],
        );

        if ($redemption->reward->stock !== null) {
            $redemption->reward->increment('stock', (int) $redemption->quantity);
        }
    }
}
