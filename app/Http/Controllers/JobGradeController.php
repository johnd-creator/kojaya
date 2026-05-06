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
        $this->authorizePermission('manage_job_grades');

        $jobGrades = JobGrade::orderBy('level')->withCount('positions')->get();

        return Inertia::render('JobGrade/Index', [
            'jobGrades' => $jobGrades,
        ]);
    }

    public function store(UpsertJobGradeRequest $request)
    {
        $this->authorizePermission('manage_job_grades');

        JobGrade::create($request->validated());

        return redirect()->route('job-grades.index')->with('success', 'Job grade created.');
    }

    public function update(UpsertJobGradeRequest $request, JobGrade $jobGrade)
    {
        $this->authorizePermission('manage_job_grades');

        $jobGrade->update($request->validated());

        return redirect()->route('job-grades.index')->with('success', 'Job grade updated.');
    }
}
