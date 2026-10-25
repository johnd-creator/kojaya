<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSessionRenewal extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['consumed_at' => 'immutable_datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MemberAuthSession::class, 'member_auth_session_id');
    }
}
