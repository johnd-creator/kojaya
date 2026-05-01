<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'user_id',
        'member_no',
        'name',
        'email',
        'phone',
        'identity_number',
        'address',
        'joined_at',
        'resigned_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'resigned_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CooperativeMemberDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CooperativeDuesInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CooperativePayment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CooperativeLedgerEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}
