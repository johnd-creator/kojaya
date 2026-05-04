<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertWorkShiftRequest;
use App\Models\WorkShift;
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

    public function store(UpsertWorkShiftRequest $request)
    {
        WorkShift::create($request->validated());

        return redirect()->route('work-shifts.index')->with('success', 'Work shift created.');
    }

    public function update(UpsertWorkShiftRequest $request, WorkShift $workShift)
    {
        $workShift->update($request->validated());

        return redirect()->route('work-shifts.index')->with('success', 'Work shift updated.');
    }
}
