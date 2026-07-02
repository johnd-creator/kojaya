<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberResignationRequest extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'cooperative_member_id',
        'user_id',
        'reviewed_by',
        'status',
        'reason',
        'effective_date',
        'review_notes',
        'requested_at',
        'reviewed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
