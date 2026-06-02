<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LoanRestructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'cooperative_member_id',
        'requested_by',
        'reviewed_by',
        'status',
        'reason',
        'proposed_principal_amount',
        'proposed_interest_rate',
        'proposed_term_months',
        'proposed_first_due_date',
        'admin_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_principal_amount' => 'decimal:2',
            'proposed_interest_rate' => 'decimal:4',
            'proposed_first_due_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function approvalLogs(): MorphMany
    {
        return $this->morphMany(ApprovalLog::class, 'subject');
    }
}
