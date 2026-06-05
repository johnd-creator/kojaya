<?php

namespace App\Models;

use App\Enums\PayrollApprovalStatus;
use App\Models\Traits\HasApprovalLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollApproval extends Model
{
    use HasApprovalLog, HasFactory, HasUuids;

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
        return $query->where('status', PayrollApprovalStatus::Pending->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PayrollApprovalStatus::Approved->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', PayrollApprovalStatus::Rejected->value);
    }

    public function approve(User $approver, ?string $notes = null): void
    {
        if ($approver->id === $this->requester_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'approved_by' => 'Pengaju payroll tidak dapat menyetujui payrollnya sendiri.',
            ]);
        }

        $this->update([
            'status' => PayrollApprovalStatus::Approved->value,
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);

        $this->logApproval(PayrollApprovalStatus::Pending->value, PayrollApprovalStatus::Approved->value, $approver, $notes);
    }

    public function reject(User $approver, ?string $notes = null): void
    {
        $this->update([
            'status' => PayrollApprovalStatus::Rejected->value,
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);

        $this->logApproval(PayrollApprovalStatus::Pending->value, PayrollApprovalStatus::Rejected->value, $approver, $notes);
    }
}
