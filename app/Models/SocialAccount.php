<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

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
     * Encrypt sensitive token payloads at rest. The getters gracefully
     * return null when decryption fails (e.g. after an APP_KEY rotation
     * or when a token is corrupt) so a stale token never crashes the
     * request — the member can simply re-link the provider.
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSafely($value, 'access_token'),
            set: fn ($value) => $value === null ? null : encrypt($value),
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSafely($value, 'refresh_token'),
            set: fn ($value) => $value === null ? null : encrypt($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    private function decryptSafely(?string $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (DecryptException $exception) {
            Log::warning('social_account.token_decrypt_failed', [
                'social_account_id' => $this->id,
                'provider' => $this->provider,
                'field' => $field,
            ]);

            return null;
        }
    }
}
