<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeShuAllocation extends Model
{
    protected $fillable = [
        'cooperative_shu_period_id',
        'cooperative_member_id',
        'membership_score',
        'dues_score',
        'shu_score',
        'cooperative_shu_amount',
        'pos_points',
        'pos_shu_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'membership_score' => 'decimal:2',
            'dues_score' => 'decimal:2',
            'shu_score' => 'decimal:2',
            'cooperative_shu_amount' => 'decimal:2',
            'pos_shu_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(CooperativeShuPeriod::class, 'cooperative_shu_period_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }
}
