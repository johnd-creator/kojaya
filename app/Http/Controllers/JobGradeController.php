<?php

namespace App\Http\Controllers;

use App\Models\JobGrade;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:job_grades,code',
            'name' => 'required|string|max:100',
            'level' => 'required|integer|min:1',
        ]);

        JobGrade::create($validated);

        return redirect()->route('job-grades.index')->with('success', 'Job grade created.');
    }

    public function update(Request $request, JobGrade $jobGrade)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:job_grades,code,'.$jobGrade->id,
            'name' => 'required|string|max:100',
            'level' => 'required|integer|min:1',
        ]);

        $jobGrade->update($validated);

        return redirect()->route('job-grades.index')->with('success', 'Job grade updated.');
    }
}
