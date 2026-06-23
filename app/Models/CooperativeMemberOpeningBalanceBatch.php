<?php

namespace App\Models;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeMemberOpeningBalanceBatch extends Model
{
    /** @use HasFactory<\Database\Factories\CooperativeMemberOpeningBalanceBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'cooperative_member_id',
        'organization_id',
        'status',
        'calculation_start_period',
        'calculation_end_period',
        'months_count',
        'total_amount',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'source_type',
        'source_reference',
        'source_document_date',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => OpeningBalanceBatchStatus::class,
            'calculation_start_period' => 'date',
            'calculation_end_period' => 'date',
            'months_count' => 'integer',
            'total_amount' => 'decimal:2',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
            'source_document_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CooperativeMemberOpeningBalanceLine::class, 'opening_balance_batch_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isDraft(): bool
    {
        return $this->status === OpeningBalanceBatchStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === OpeningBalanceBatchStatus::Posted;
    }

    public function isVoided(): bool
    {
        return $this->status === OpeningBalanceBatchStatus::Voided;
    }

    public function canBeVoided(): bool
    {
        return $this->isPosted();
    }

    public function recalculateTotal(): void
    {
        $this->forceFill([
            'total_amount' => $this->lines()->sum('total_amount'),
        ])->save();
    }
}
