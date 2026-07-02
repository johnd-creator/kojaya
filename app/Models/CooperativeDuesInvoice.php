<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CooperativeDuesInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cooperative_member_id',
        'cooperative_contribution_type_id',
        'period',
        'amount',
        'paid_amount',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(CooperativeContributionType::class, 'cooperative_contribution_type_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CooperativePayment::class);
    }

    public function scopeForActiveMembers(Builder $query): Builder
    {
        return $query->whereHas('member', fn (Builder $memberQuery) => $memberQuery->active());
    }

    public function scopeForSavingsDues(Builder $query): Builder
    {
        return $query->whereHas('contributionType', fn (Builder $typeQuery) => $typeQuery->savingsDues());
    }

    public function isSavingsDues(): bool
    {
        return (bool) $this->contributionType?->isSavingsDues();
    }
}
