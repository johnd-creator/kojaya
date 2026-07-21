<?php

namespace App\Models;

use App\Enums\MemberStoreAccountStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberStoreAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'cooperative_member_id',
        'credit_limit',
        'status',
        'opened_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'credit_limit' => 'integer',
            'status' => MemberStoreAccountStatus::class,
            'opened_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(MemberStoreLedgerEntry::class, 'account_id')->orderByDesc('occurred_at');
    }

    public function fundingRequests(): HasMany
    {
        return $this->hasMany(MemberStoreFundingRequest::class, 'account_id');
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(MemberStoreDelegate::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MemberStoreAccountStatus::Active->value);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', MemberStoreAccountStatus::Suspended->value);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', MemberStoreAccountStatus::Closed->value);
    }

    public function signedBalance(): int
    {
        return (int) $this->balance;
    }

    public function availableCredit(): int
    {
        return (int) $this->balance + (int) $this->credit_limit;
    }

    public function canPurchase(): bool
    {
        return $this->status->canPurchase();
    }

    public function canReceiveFunding(): bool
    {
        return $this->status->canReceiveFunding();
    }
}
