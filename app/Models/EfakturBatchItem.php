<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfakturBatchItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'batch_id',
        'invoice_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EfakturBatch::class, 'batch_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
