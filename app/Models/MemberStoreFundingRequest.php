<?php

namespace App\Models;

use App\Enums\MemberStoreFundingMethod;
use App\Enums\MemberStoreFundingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStoreFundingRequest extends Model
{
    protected $hidden = [
        'proof_path',
    ];

    protected $fillable = [
        'account_id',
        'organization_id',
        'method',
        'amount',
        'status',
        'proof_path',
        'bank_reference',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'idempotency_key',
        'posted_ledger_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => MemberStoreFundingMethod::class,
            'status' => MemberStoreFundingStatus::class,
            'reviewed_at' => 'datetime',
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

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function postedLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(MemberStoreLedgerEntry::class, 'posted_ledger_entry_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', MemberStoreFundingStatus::Pending->value);
    }
}
