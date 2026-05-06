<?php

namespace App\Models;

use App\Models\Traits\HasApprovalLog;
use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reimbursement extends Model
{
    use HasApprovalLog, HasFactory, HasOrganizationScope, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'user_id',
        'project_id',
        'approver_id',
        'submission_date',
        'total_amount',
        'status',
        'description',
        'rejection_reason',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class);
    }
}
