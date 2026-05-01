<?php

namespace App\Console\Commands;

use App\Models\ShiftRoster;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateShiftRoster extends Command
{
    protected $signature = 'shift:generate {year} {month}';

    protected $description = 'Generate rotating shift roster for all groups (A/B/C/D) for a given month.';

    /**
     * Shift cycle pattern for 8 days: indices 0-5 are work days (pointing to shift type), 6-7 are OFF.
     * Pattern per day index: Pagi(0,1), Siang(2,3), Malam(4,5), OFF(6,7)
     * Group offsets: A=0, B=2, C=4, D=6 (starting offset in cycle).
     *
     * @var array<string, int>
     */
    protected array $groupOffsets = [
        'A' => 0,
        'B' => 2,
        'C' => 4,
        'D' => 6,
    ];

    /** Cycle length: 6 work + 2 off = 8 days */
    protected int $cycleLength = 8;

    /**
     * Reference epoch: the start of the cycle calculation.
     * Adjust this date if the actual Group A cycle start is known.
     */
    protected string $referenceDate = '2026-01-01';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $month = (int) $this->argument('month');

        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            $this->error('Invalid year or month.');

            return Command::FAILURE;
        }

        $workShifts = WorkShift::where('type', 'SHIFT')
            ->orderBy('start_time')
            ->get();

        if ($workShifts->count() < 3) {
            $this->error('Need at least 3 SHIFT-type work shifts (Pagi, Siang, Malam) in work_shifts table.');

            return Command::FAILURE;
        }

        /** @var WorkShift $shiftPagi */
        $shiftPagi = $workShifts->get(0);
        /** @var WorkShift $shiftSiang */
        $shiftSiang = $workShifts->get(1);
        /** @var WorkShift $shiftMalam */
        $shiftMalam = $workShifts->get(2);

        /**
         * Shift pattern within 8-day cycle (indexed 0-7).
         *
         * @var array<int, array{work_shift_id: int|null, is_off_day: bool}>
         */
        $cyclePattern = [
            0 => ['work_shift_id' => $shiftPagi->id, 'is_off_day' => false],
            1 => ['work_shift_id' => $shiftPagi->id, 'is_off_day' => false],
            2 => ['work_shift_id' => $shiftSiang->id, 'is_off_day' => false],
            3 => ['work_shift_id' => $shiftSiang->id, 'is_off_day' => false],
            4 => ['work_shift_id' => $shiftMalam->id, 'is_off_day' => false],
            5 => ['work_shift_id' => $shiftMalam->id, 'is_off_day' => false],
            6 => ['work_shift_id' => null, 'is_off_day' => true],
            7 => ['work_shift_id' => null, 'is_off_day' => true],
        ];

        $reference = Carbon::parse($this->referenceDate)->startOfDay();
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $records = [];
        $groups = array_keys($this->groupOffsets);

        $current = $startOfMonth->copy();
        while ($current <= $endOfMonth) {
            $daysSinceRef = (int) $reference->diffInDays($current);

            foreach ($groups as $group) {
                $offset = $this->groupOffsets[$group];
                $cycleIndex = ($daysSinceRef + $offset) % $this->cycleLength;
                $pattern = $cyclePattern[$cycleIndex];

                $records[] = [
                    'date' => $current->toDateString(),
                    'shift_group' => $group,
                    'work_shift_id' => $pattern['work_shift_id'],
                    'is_off_day' => $pattern['is_off_day'],
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $current->addDay();
        }

        // Upsert so re-running is safe (won't duplicate, will update if changed)
        ShiftRoster::upsert(
            $records,
            ['date', 'shift_group'],          // unique keys
            ['work_shift_id', 'is_off_day', 'updated_at']  // columns to update on conflict
        );

        $this->info(sprintf(
            'Generated %d roster entries for %s %d.',
            count($records),
            Carbon::create($year, $month)->format('F'),
            $year
        ));

        return Command::SUCCESS;
    }
}
