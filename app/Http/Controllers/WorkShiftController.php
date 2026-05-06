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
        $this->authorizePermission('manage_work_shifts');

        $shifts = WorkShift::orderByRaw("CASE type WHEN 'SHIFT' THEN 0 ELSE 1 END")->orderBy('start_time')->get();

        return Inertia::render('WorkShift/Index', [
            'shifts' => $shifts,
        ]);
    }

    public function store(UpsertWorkShiftRequest $request)
    {
        $this->authorizePermission('manage_work_shifts');

        WorkShift::create($request->validated());

        return redirect()->route('work-shifts.index')->with('success', 'Work shift created.');
    }

    public function update(UpsertWorkShiftRequest $request, WorkShift $workShift)
    {
        $this->authorizePermission('manage_work_shifts');

        $workShift->update($request->validated());

        return redirect()->route('work-shifts.index')->with('success', 'Work shift updated.');
    }
}
