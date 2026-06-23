<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeMemberOpeningBalanceLine extends Model
{
    /** @use HasFactory<\Database\Factories\CooperativeMemberOpeningBalanceLineFactory> */
    use HasFactory;

    protected $fillable = [
        'opening_balance_batch_id',
        'cooperative_contribution_type_id',
        'category_snapshot',
        'period_start',
        'period_end',
        'months_count',
        'unit_amount',
        'total_amount',
        'calculation_method',
        'override_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'months_count' => 'integer',
            'unit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CooperativeMemberOpeningBalanceBatch::class, 'opening_balance_batch_id');
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(CooperativeContributionType::class, 'cooperative_contribution_type_id');
    }
}
