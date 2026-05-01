<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkShiftController extends Controller
{
    public function index(): Response
    {
        $shifts = WorkShift::orderByRaw("CASE type WHEN 'SHIFT' THEN 0 ELSE 1 END")->orderBy('start_time')->get();

        return Inertia::render('WorkShift/Index', [
            'shifts' => $shifts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:work_shifts,name',
            'type' => 'required|in:SHIFT,NON_SHIFT',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_flexible' => 'boolean',
            'flexible_minutes' => 'integer|min:0|max:120',
        ]);

        WorkShift::create($validated);

        return redirect()->route('work-shifts.index')->with('success', 'Work shift created.');
    }

    public function update(Request $request, WorkShift $workShift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:work_shifts,name,'.$workShift->id,
            'type' => 'required|in:SHIFT,NON_SHIFT',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_flexible' => 'boolean',
            'flexible_minutes' => 'integer|min:0|max:120',
        ]);

        $workShift->update($validated);

        return redirect()->route('work-shifts.index')->with('success', 'Work shift updated.');
    }
}
