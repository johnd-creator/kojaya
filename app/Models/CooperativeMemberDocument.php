<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeMemberDocument extends Model
{
    protected $fillable = [
        'cooperative_member_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }
}
