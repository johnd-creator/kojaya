<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeShuPeriod extends Model
{
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
    ];

    protected function casts(): array
    {
        return [
            'cooperative_pool' => 'decimal:2',
            'pos_profit_pool' => 'decimal:2',
            'total_membership_score' => 'decimal:2',
            'total_dues_score' => 'decimal:2',
            'total_shu_score' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CooperativeShuAllocation::class);
    }
}
