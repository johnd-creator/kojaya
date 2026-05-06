<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeSupportTicket extends Model
{
    protected $fillable = [
        'cooperative_member_id',
        'user_id',
        'ticket_no',
        'category',
        'priority',
        'subject',
        'message',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
