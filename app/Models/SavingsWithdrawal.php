<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_member_id',
        'user_id',
        'approved_by',
        'amount',
        'status',
        'destination_bank',
        'destination_account_no',
        'destination_account_name',
        'reason',
        'rejection_reason',
        'approved_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => WithdrawalStatus::class,
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }
}
