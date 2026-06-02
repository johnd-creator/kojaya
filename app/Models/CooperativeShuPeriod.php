<?php

namespace App\Models;

use App\Enums\CooperativeShuPeriodStatus;
use App\Models\Traits\HasApprovalLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeShuPeriod extends Model
{
    use HasApprovalLog;

    protected $fillable = [
        'year',
        'cooperative_pool',
        'pos_profit_pool',
        'total_membership_score',
        'total_dues_score',
        'total_shu_score',
        'total_pos_points',
        'status',
        'closed_at',
        'closed_by',
        'revision_reason',
        'revision_requested_by',
        'revision_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'cooperative_pool' => 'decimal:2',
            'pos_profit_pool' => 'decimal:2',
            'total_membership_score' => 'decimal:2',
            'total_dues_score' => 'decimal:2',
            'total_shu_score' => 'decimal:2',
            'status' => CooperativeShuPeriodStatus::class,
            'closed_at' => 'datetime',
            'revision_requested_at' => 'datetime',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CooperativeShuAllocation::class);
    }
}
