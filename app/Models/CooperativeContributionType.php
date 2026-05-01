<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeContributionType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'default_amount',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CooperativeDuesInvoice::class);
    }
}
