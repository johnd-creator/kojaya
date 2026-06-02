<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationOutbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'channel',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'available_at',
        'processing_at',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'processing_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
