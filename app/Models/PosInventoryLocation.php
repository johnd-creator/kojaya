<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosInventoryLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location_type',
        'address',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(PosInventoryStock::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PosStockReceipt::class);
    }

    public function counts(): HasMany
    {
        return $this->hasMany(PosStockCount::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(PosStockTransfer::class, 'from_location_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(PosStockTransfer::class, 'to_location_id');
    }
}
