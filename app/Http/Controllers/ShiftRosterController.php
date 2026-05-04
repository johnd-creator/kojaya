<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateShiftRosterRequest;
use App\Http\Requests\UpdateShiftRosterRequest;
use App\Models\ShiftRoster;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftRosterController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->month));

        $rosters = ShiftRoster::with('workShift')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->orderBy('shift_group')
            ->get()
            ->groupBy(fn ($r) => $r->date->toDateString());

        $workShifts = WorkShift::where('type', 'SHIFT')
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        return Inertia::render('ShiftRoster/Index', [
            'rosters' => $rosters,
            'workShifts' => $workShifts,
            'year' => $year,
            'month' => $month,
            'daysInMonth' => $daysInMonth,
            'groups' => ['A', 'B', 'C', 'D'],
        ]);
    }

    public function update(UpdateShiftRosterRequest $request, ShiftRoster $shiftRoster): RedirectResponse
    {
        $validated = $request->validated();

        $shiftRoster->update([
            'work_shift_id' => $validated['is_off_day'] ? null : $validated['work_shift_id'],
            'is_off_day' => $validated['is_off_day'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Roster entry updated.');
    }

    public function generate(GenerateShiftRosterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        \Artisan::call('shift:generate', [
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]);

        return back()->with('success', sprintf(
            'Roster for %s %d has been generated.',
            Carbon::create($validated['year'], $validated['month'])->format('F'),
            $validated['year']
        ));
    }
}
