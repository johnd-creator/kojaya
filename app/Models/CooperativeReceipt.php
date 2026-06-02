<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperativeReceipt extends Model
{
    /** @use HasFactory<\Database\Factories\CooperativeReceiptFactory> */
    use HasFactory;

    protected $fillable = [
        'receipt_no',
        'cooperative_payment_id',
        'cooperative_member_id',
        'pdf_path',
        'issued_at',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CooperativePayment::class, 'cooperative_payment_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
