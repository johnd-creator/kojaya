<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSyncRequest extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_DONE = 'DONE';

    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'client_id',
        'device_id',
        'user_id',
        'pos_cashier_shift_id',
        'endpoint',
        'method',
        'payload',
        'payload_hash',
        'headers',
        'idempotency_key',
        'response_status',
        'response_body',
        'status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'response_body' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
