<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransferBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'bank_name',
        'account_number',
        'status',
        'format',
        'batch_date',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankTransferItem::class, 'batch_id');
    }
}
