<?php

namespace App\Models;

use App\Contracts\OrganizationScopedModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCategory extends Model implements OrganizationScopedModel
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'duplicated_from_id',
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PosCategory $category): void {
            if ($category->isDirty('organization_id') && $category->getOriginal('organization_id') !== null) {
                if ((string) $category->organization_id !== (string) $category->getOriginal('organization_id')) {
                    throw new \InvalidArgumentException('Organization ownership of a POS category is immutable.');
                }
            }
        });
    }

    public function organizationScopePath(): string
    {
        return 'organization_id';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PosProduct::class);
    }
}
