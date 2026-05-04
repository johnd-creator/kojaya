<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PointTransactionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'cooperative_member_id',
        'transaction_type',
        'points',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'reference_number',
        'description',
        'posted_at',
        'expires_at',
        'metadata',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'posted_at' => 'date',
            'expires_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }
}
