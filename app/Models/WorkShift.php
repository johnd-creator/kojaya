<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'start_time',
        'end_time',
        'is_flexible',
        'flexible_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_flexible' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Calculate scheduled end time for non-shift employees.
     * If clock_in is later than start_time, add the difference to end_time.
     */
    public function scheduledEndTime(string $clockIn): string
    {
        if (! $this->is_flexible) {
            return $this->end_time;
        }

        $startMinutes = $this->timeToMinutes($this->start_time);
        $endMinutes = $this->timeToMinutes($this->end_time);
        $clockInMinutes = $this->timeToMinutes($clockIn);
        $lateMinutes = max(0, $clockInMinutes - $startMinutes);
        $scheduledMinutes = $endMinutes + $lateMinutes;

        return sprintf('%02d:%02d:00', intdiv($scheduledMinutes, 60) % 24, $scheduledMinutes % 60);
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return ((int) $h * 60) + (int) $m;
    }
}
