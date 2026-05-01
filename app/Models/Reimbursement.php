<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reimbursement extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids, SoftDeletes;

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

    protected $casts = [
        'submission_date' => 'date',
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(ReimbursementItem::class);
    }
}
