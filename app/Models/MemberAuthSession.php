<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAuthSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['security_digest', 'device_id'];

    protected function casts(): array
    {
        return ['remembered' => 'boolean', 'idle_expires_at' => 'immutable_datetime', 'absolute_expires_at' => 'immutable_datetime', 'last_renewed_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
