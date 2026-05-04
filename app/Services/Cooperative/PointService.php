<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\Reward;
use App\Models\RewardRedemption;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function syncPosPoints(CooperativeMember $member): void
    {
        $existingSources = PointTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->where('source_type', PosMemberPoint::class)
            ->pluck('source_id')
            ->all();

        $points = $member->posMemberPoints()
            ->whereNotIn('id', $existingSources)
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        foreach ($points as $point) {
            $this->recordTransaction(
                member: $member,
                transactionType: 'EARNED',
                points: (int) $point->points,
                description: 'Poin dari transaksi POS koperasi',
                postedAt: Carbon::parse($point->posted_at),
                sourceType: PosMemberPoint::class,
                sourceId: (string) $point->id,
                referenceNumber: optional($point->transaction)->transaction_number,
                metadata: [
                    'profit_amount' => (float) $point->profit_amount,
                ],
            );
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

            $transaction = $this->recordTransaction(
                member: $member,
                transactionType: 'REDEEMED',
                points: $pointsRequired * -1,
                description: 'Penukaran reward: '.$lockedReward->name,
                postedAt: now(),
                sourceType: Reward::class,
                sourceId: $lockedReward->id,
                referenceNumber: null,
                metadata: [
                    'reward_name' => $lockedReward->name,
                    'quantity' => $quantity,
                ],
            );

            if ($lockedReward->stock !== null) {
                $lockedReward->decrement('stock', $quantity);
            }

            return RewardRedemption::query()->create([
                'reward_id' => $lockedReward->id,
                'cooperative_member_id' => $member->id,
                'point_transaction_id' => $transaction->id,
                'quantity' => $quantity,
                'points_used' => $pointsRequired,
                'delivery_address' => $deliveryAddress,
                'status' => 'PENDING',
                'redeemed_at' => now(),
            ]);
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
        Carbon $postedAt,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $referenceNumber = null,
        ?Carbon $expiresAt = null,
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
}
