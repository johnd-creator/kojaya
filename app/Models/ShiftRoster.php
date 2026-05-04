<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'shift_group',
        'work_shift_id',
        'is_off_day',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_off_day' => 'boolean',
        ];
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * Get today's roster entry for a given shift group.
     */
    public static function todayFor(string $shiftGroup): ?self
    {
        return self::where('shift_group', $shiftGroup)
            ->where('date', today()->toDateString())
            ->with('workShift')
            ->first();
    }
}
