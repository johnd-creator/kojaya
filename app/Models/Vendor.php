<?php

namespace App\Models;

use App\Models\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    use HasFactory, HasOrganizationScope, HasUuids;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'status',
        'rating',
        'email',
        'phone',
        'tax_id',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
