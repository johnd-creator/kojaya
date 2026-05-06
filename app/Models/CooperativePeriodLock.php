<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativePeriodLock extends Model
{
    protected $fillable = [
        'period',
        'module',
        'status',
        'reason',
        'locked_at',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function isLocked(): bool
    {
        return $this->status === 'LOCKED' && $this->unlocked_at === null;
    }
}
