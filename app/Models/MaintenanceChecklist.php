<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceChecklist extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'category',
        'description',
        'checklist_items',
        'asset_category_id',
        'organization_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'checklist_items' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class, 'maintenance_checklist_id');
    }
}
