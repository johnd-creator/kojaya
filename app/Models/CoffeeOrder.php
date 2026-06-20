<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeeOrder extends Model
{
    /** @use HasFactory<\Database\Factories\CoffeeOrderFactory> */
    use HasFactory;

    public const STATUS_RECEIVED = 'RECEIVED';

    public const STATUS_BREWING = 'BREWING';

    public const STATUS_READY = 'READY';

    public const STATUS_PICKED_UP = 'PICKED_UP';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'pos_transaction_id',
        'cooperative_member_id',
        'pos_product_id',
        'prepared_by',
        'completed_by',
        'quantity',
        'status',
        'customization',
        'received_at',
        'brewing_at',
        'ready_at',
        'picked_up_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'customization' => 'array',
            'received_at' => 'datetime',
            'brewing_at' => 'datetime',
            'ready_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_RECEIVED,
            self::STATUS_BREWING,
            self::STATUS_READY,
            self::STATUS_PICKED_UP,
            self::STATUS_CANCELLED,
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_BREWING => 'Kopi Sedang Diseduh',
            self::STATUS_READY => 'Kopi Siap Diambil',
            self::STATUS_PICKED_UP => 'Pesanan Selesai',
            self::STATUS_CANCELLED => 'Pesanan Dibatalkan',
            default => 'Pesanan Diterima',
        };
    }

    public function mobileStep(): int
    {
        return match ($this->status) {
            self::STATUS_BREWING => 1,
            self::STATUS_READY, self::STATUS_PICKED_UP => 2,
            self::STATUS_CANCELLED => -1,
            default => 0,
        };
    }
}
