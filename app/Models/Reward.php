<?php

namespace App\Models;

use App\Contracts\OrganizationScopedModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model implements OrganizationScopedModel
{
    /** @use HasFactory<\Database\Factories\RewardFactory> */
    use HasFactory, HasUuids;

    public function organizationScopePath(): string
    {
        return 'organization_id';
    }

    protected $fillable = [
        'organization_id',
        'name',
        'category',
        'description',
        'points_required',
        'stock',
        'valid_until',
        'image_url',
        'is_active',
        'metadata',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}
