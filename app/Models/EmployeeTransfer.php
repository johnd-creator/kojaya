<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class EmployeeTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from_organization_id',
        'to_organization_id',
        'effective_date',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $allAccessRoles = ['System Admin', 'Admin Pusat', 'HR Pusat', 'Finance Pusat'];

        if ($user->hasAnyRole($allAccessRoles)) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('from_organization_id', $user->organization_id)
                ->orWhere('to_organization_id', $user->organization_id);
        });
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

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    public function approve(User $approver, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'APPROVED',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        return true;
    }

    public function reject(User $approver, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'REJECTED',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        return true;
    }
}
