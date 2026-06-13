<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosInventoryStock extends Model
{
    protected $fillable = [
        'pos_product_id',
        'pos_inventory_location_id',
        'quantity',
        'reserved',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'pos_inventory_location_id');
    }
}
