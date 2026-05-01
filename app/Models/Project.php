<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
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

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'progress_percentage' => 'integer',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function team()
    {
        return $this->hasMany(ProjectTeam::class);
    }

    public function assetAllocations()
    {
        return $this->hasMany(ProjectAssetAllocation::class);
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }

    // Financial Relationships
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function pettyCashTransactions()
    {
        return $this->hasMany(PettyCashTransaction::class);
    }

    public function budgetItems()
    {
        return $this->hasMany(ProjectBudgetItem::class);
    }

    public function payrollAllocations()
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

    public function scopePending($query)
    {
        return $query->where('status', 'PLANNING');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ONGOING');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }
}
