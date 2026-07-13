<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeNotificationOutbox extends Model
{
    protected $table = 'cooperative_notification_outbox';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'deduplication_key',
        'payload',
        'status',
        'attempts',
        'available_at',
        'delivered_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'payload' => 'array',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
