<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids;

    protected $fillable = [
        'id',
        'project_code',
        'name',
        'description',
        'organization_id',
        'client_id',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'status',
        'progress_percentage',
        'notes',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'progress_percentage' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function team(): HasMany
    {
        return $this->hasMany(ProjectTeam::class);
    }

    public function assetAllocations(): HasMany
    {
        return $this->hasMany(ProjectAssetAllocation::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function pettyCashTransactions(): HasMany
    {
        return $this->hasMany(PettyCashTransaction::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ProjectBudgetItem::class);
    }

    public function payrollAllocations(): HasMany
    {
        return $this->hasMany(ProjectPayrollAllocation::class);
    }

    public function getBudgetVarianceAttribute(): float
    {
        return (float) ($this->budget - $this->actual_cost);
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->actual_cost > $this->budget;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date < now() && ! in_array($this->status, ['COMPLETED', 'CANCELLED']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PLANNING');
    }

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', 'ONGOING');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'COMPLETED');
    }
}
