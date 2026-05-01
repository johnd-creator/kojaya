<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollApproval extends Model
{
    protected $fillable = [
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

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    public function approve(User $approver, ?string $notes = null)
    {
        $this->update([
            'status' => 'APPROVED',
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $approver, ?string $notes = null)
    {
        $this->update([
            'status' => 'REJECTED',
            'approver_id' => $approver->id,
            'approver_notes' => $notes,
            'approved_at' => now(),
        ]);
    }
}
