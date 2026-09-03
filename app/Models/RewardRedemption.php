<?php

namespace App\Models;

use App\Contracts\OrganizationScopedModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model implements OrganizationScopedModel
{
    /** @use HasFactory<\Database\Factories\RewardRedemptionFactory> */
    use HasFactory, HasUuids;

    public function organizationScopePath(): string
    {
        return 'member.organization_id';
    }

    protected $fillable = [
        'reward_id',
        'cooperative_member_id',
        'point_transaction_id',
        'quantity',
        'points_used',
        'delivery_address',
        'status',
        'notes',
        'redeemed_at',
        'processed_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function pointTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class);
    }
}
