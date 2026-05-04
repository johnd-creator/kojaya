<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollApproval extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'payroll_id',
        'payroll_batch_id',
        'requester_id',
        'approver_id',
        'status',
        'requester_notes',
        'approver_notes',
        'requested_at',
        'approved_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'REJECTED');
    }

    public function approve(User $approver, ?string $notes = null): void
    {
        $this->update([
            'status' => 'APPROVED',
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $approver, ?string $notes = null): void
    {
        $this->update([
            'status' => 'REJECTED',
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);
    }
}
