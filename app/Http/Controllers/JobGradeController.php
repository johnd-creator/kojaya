<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertJobGradeRequest;
use App\Models\JobGrade;
use Inertia\Inertia;
use Inertia\Response;

class JobGradeController extends Controller
{
    public function index(): Response
    {
        $jobGrades = JobGrade::orderBy('level')->withCount('positions')->get();

        return Inertia::render('JobGrade/Index', [
            'jobGrades' => $jobGrades,
        ]);
    }

    public function store(UpsertJobGradeRequest $request)
    {
        JobGrade::create($request->validated());

        return redirect()->route('job-grades.index')->with('success', 'Job grade created.');
    }

    public function update(UpsertJobGradeRequest $request, JobGrade $jobGrade)
    {
        $jobGrade->update($request->validated());

        return redirect()->route('job-grades.index')->with('success', 'Job grade updated.');
    }
}
