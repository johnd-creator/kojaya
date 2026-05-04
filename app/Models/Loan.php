<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_member_id',
        'organization_id',
        'loan_type_id',
        'user_id',
        'principal_amount',
        'interest_rate',
        'admin_fee',
        'late_fee_per_day',
        'term_months',
        'installment_amount',
        'total_interest_amount',
        'total_amount',
        'outstanding_amount',
        'applied_at',
        'first_due_date',
        'approved_at',
        'approved_by',
        'disbursed_at',
        'disbursed_by',
        'rejected_at',
        'rejected_by',
        'status',
        'reference_no',
        'purpose',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'admin_fee' => 'decimal:2',
            'late_fee_per_day' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'total_interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'applied_at' => 'date',
            'first_due_date' => 'date',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'status' => LoanStatus::class,
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CooperativeMember::class, 'cooperative_member_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function approvalLogs(): MorphMany
    {
        return $this->morphMany(ApprovalLog::class, 'subject');
    }
}
