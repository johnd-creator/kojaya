<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_days_allowance',
        'requires_attachment',
        'is_paid',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_attachment' => 'boolean',
            'is_paid' => 'boolean',
        ];
    }

    /**
     * Get the leaves associated with this type.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
