<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'interest_rate',
        'admin_fee',
        'late_fee_per_day',
        'min_amount',
        'max_amount',
        'min_term_months',
        'max_term_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interest_rate' => 'decimal:4',
            'admin_fee' => 'decimal:2',
            'late_fee_per_day' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
