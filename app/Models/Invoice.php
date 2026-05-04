<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'client_id',
        'project_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'amount',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'unit_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'DRAFT');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'PAID');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'OVERDUE');
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['PENDING', 'APPROVED']) &&
               $this->due_date->isPast();
    }

    public function calculateTax(float $rate = 0.11): float
    {
        return (float) ($this->amount * $rate);
    }

    public function calculateTotal(): float
    {
        return (float) $this->amount + (float) $this->tax_amount;
    }
}
