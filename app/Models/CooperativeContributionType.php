<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperativeContributionType extends Model
{
    use HasFactory;

    public const SAVINGS_DUES_CATEGORIES = ['POKOK', 'WAJIB'];

    protected $fillable = [
        'code',
        'name',
        'category',
        'default_amount',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CooperativeDuesInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CooperativePayment::class);
    }

    public function scopeSavingsDues(Builder $query): Builder
    {
        return $query->where(function (Builder $typeQuery): void {
            $typeQuery
                ->whereIn('category', self::SAVINGS_DUES_CATEGORIES)
                ->orWhereIn('code', self::SAVINGS_DUES_CATEGORIES);
        });
    }

    public function isSavingsDues(): bool
    {
        return in_array($this->category, self::SAVINGS_DUES_CATEGORIES, true)
            || in_array($this->code, self::SAVINGS_DUES_CATEGORIES, true);
    }
}
