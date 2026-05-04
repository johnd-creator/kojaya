<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'address',
        'tax_id',
        'contact_person',
        'phone',
        'email',
        'client_type',
        'organization_id',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopePln(Builder $query): Builder
    {
        return $query->where('client_type', 'PLN');
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('client_type', 'PRIVATE');
    }
}
