<?php

namespace App\Models;

use App\Enums\McuResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalCheckup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'checkup_date',
        'next_checkup_date',
        'result',
        'fit_to_work',
        'notes',
        'document_path',
        'doctor_name',
        'clinic_name',
    ];

    protected function casts(): array
    {
        return [
            'checkup_date' => 'date',
            'next_checkup_date' => 'date',
            'result' => McuResult::class,
            'fit_to_work' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeDue(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('next_checkup_date', [now(), now()->addDays($days)]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('checkup_date', '<=', now());
    }

    public function isDue(): bool
    {
        if (! $this->next_checkup_date) {
            return false;
        }

        return $this->next_checkup_date->lte(now()->addDays(30)) && $this->next_checkup_date->gt(now());
    }

    public function isOverdue(): bool
    {
        if (! $this->next_checkup_date) {
            return false;
        }

        return $this->next_checkup_date->isPast();
    }

    public function getDaysUntilDue(): ?int
    {
        if (! $this->next_checkup_date) {
            return null;
        }

        return now()->diffInDays($this->next_checkup_date, false);
    }

    public function calculateNextCheckupDate(): ?\Carbon\Carbon
    {
        // Standard: Annual MCU, so next checkup is 1 year from last checkup
        return $this->checkup_date?->addYear();
    }

    protected static function booted()
    {
        static::creating(function ($mcu) {
            // Auto-calculate next checkup date if not set
            if (! $mcu->next_checkup_date && $mcu->checkup_date) {
                $mcu->next_checkup_date = $mcu->checkup_date->addYear();
            }

            // Auto-set fit_to_work based on result
            if ($mcu->result && ! isset($mcu->fit_to_work)) {
                $mcu->fit_to_work = $mcu->result->isFitForWork();
            }
        });
    }
}
