<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'from_status',
        'to_status',
        'approved_by',
        'note',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
