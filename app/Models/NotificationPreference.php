<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_enabled',
        'database_enabled',
        'push_enabled',
        'channels',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'database_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'channels' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'mail' => $this->email_enabled,
            'database' => $this->database_enabled,
            'push' => $this->push_enabled,
            default => true,
        };
    }

    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->email_enabled) {
            $channels[] = 'mail';
        }

        if ($this->database_enabled) {
            $channels[] = 'database';
        }

        if ($this->push_enabled) {
            $channels[] = 'push';
        }

        return $this->channels ?? $channels;
    }
}
