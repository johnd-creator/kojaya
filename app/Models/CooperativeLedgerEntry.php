<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CooperativeLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_member_id',
        'organization_id',
        'cooperative_payment_id',
        'cooperative_contribution_type_id',
        'source_type',
        'source_id',
        'entry_type',
        'ledger_scope',
        'category_snapshot',
        'debit',
        'credit',
        'period',
        'description',
        'metadata',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'posted_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CooperativePayment::class, 'cooperative_payment_id');
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(CooperativeContributionType::class, 'cooperative_contribution_type_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
