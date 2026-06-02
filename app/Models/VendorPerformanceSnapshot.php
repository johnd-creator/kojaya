<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPerformanceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'score',
        'rating',
        'on_time_delivery_rate',
        'quality_acceptance_rate',
        'purchase_order_count',
        'goods_receive_note_count',
        'calculated_at',
        'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'on_time_delivery_rate' => 'decimal:2',
            'quality_acceptance_rate' => 'decimal:2',
            'calculated_at' => 'datetime',
            'breakdown' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
