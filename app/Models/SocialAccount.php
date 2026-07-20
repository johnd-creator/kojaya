<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'provider_name',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'linked_at',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_in' => 'integer',
            'linked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Encrypt sensitive token payloads at rest.
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : decrypt($value),
            set: fn ($value) => $value === null ? null : encrypt($value),
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : decrypt($value),
            set: fn ($value) => $value === null ? null : encrypt($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
