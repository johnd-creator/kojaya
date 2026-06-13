<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCashierShift extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'shift_no',
        'cashier_id',
        'pos_inventory_location_id',
        'shift_date',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'transaction_count',
        'total_sales',
        'total_cash_sales',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'transaction_count' => 'integer',
            'total_sales' => 'decimal:2',
            'total_cash_sales' => 'decimal:2',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PosInventoryLocation::class, 'pos_inventory_location_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'pos_cashier_shift_id');
    }
}
