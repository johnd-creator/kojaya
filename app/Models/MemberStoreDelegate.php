<?php

namespace App\Models;

use App\Enums\MemberStoreDelegateStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberStoreDelegate extends Model
{
    protected $fillable = [
        'account_id',
        'organization_id',
        'user_id',
        'display_name',
        'code',
        'per_transaction_limit',
        'daily_limit',
        'valid_from',
        'expires_at',
        'status',
        'created_by',
        'revoked_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'per_transaction_limit' => 'integer',
            'daily_limit' => 'integer',
            'valid_from' => 'date',
            'expires_at' => 'date',
            'revoked_at' => 'datetime',
            'status' => MemberStoreDelegateStatus::class,
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MemberStoreAccount::class, 'account_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(MemberStoreLedgerEntry::class, 'delegate_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MemberStoreDelegateStatus::Active->value);
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== MemberStoreDelegateStatus::Active) {
            return false;
        }

        $today = Carbon::today(config('app.timezone'));

        if ($this->valid_from !== null && $today->lt($this->valid_from)) {
            return false;
        }

        return ! ($this->expires_at !== null && $today->gt($this->expires_at));
    }
}
