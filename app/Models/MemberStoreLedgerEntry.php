<?php

namespace App\Models;

use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

class MemberStoreLedgerEntry extends Model
{
    protected $fillable = [
        'account_id',
        'organization_id',
        'entry_type',
        'amount',
        'effect',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'reversal_of_entry_id',
        'actor_user_id',
        'delegate_id',
        'purchaser_name',
        'purchase_note',
        'transaction_no',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'entry_type' => MemberStoreLedgerEntryType::class,
            'effect' => MemberStoreLedgerEffect::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(MemberStoreDelegate::class, 'delegate_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_entry_id');
    }

    public function reversedBy(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_entry_id');
    }

    public function isReversed(): bool
    {
        return $this->reversedBy()->exists();
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('Member store ledger entries are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function delete(): bool
    {
        throw new RuntimeException('Member store ledger entries are immutable and cannot be deleted.');
    }

    public function forceDelete(): bool
    {
        throw new RuntimeException('Member store ledger entries are immutable and cannot be deleted.');
    }
}
