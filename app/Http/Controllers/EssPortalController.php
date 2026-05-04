<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEssProfileRequest;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EssPortalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $employee = $this->employeeOrAbort($request);

        $latestPayroll = Payroll::query()
            ->where('employee_id', $employee->id)
            ->latest('period')
            ->first();

        return Inertia::render('ESS/Dashboard', [
            'employee' => $employee->load(['department', 'position', 'organization']),
            'stats' => [
                'attendance_this_month' => Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereMonth('date', now()->month)
                    ->count(),
                'pending_leaves' => Leave::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'Pending')
                    ->count(),
                'approved_leaves_this_year' => Leave::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'Approved')
                    ->whereYear('start_date', now()->year)
                    ->count(),
                'latest_payroll_period' => $latestPayroll?->period,
                'latest_net_salary' => $latestPayroll?->net_salary,
                'expiring_certificates' => $employee->certificates()->expiring(60)->count(),
                'due_medical_checkups' => $employee->medicalCheckups()->due(30)->count(),
            ],
        ]);
    }

    public function profile(Request $request): Response
    {
        $employee = $this->employeeOrAbort($request);

        return Inertia::render('ESS/Profile', [
            'user' => $request->user(),
            'employee' => $employee,
        ]);
    }

    public function updateProfile(UpdateEssProfileRequest $request): RedirectResponse
    {
        $employee = $this->employeeOrAbort($request);
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $employee->update([
            'email' => $validated['email'],
            'birth_date' => $validated['birth_date'] ?? $employee->birth_date,
            'gender' => $validated['gender'] ?? $employee->gender,
        ]);

        return back()->with('success', 'Profil ESS berhasil diperbarui.');
    }

    public function payslips(Request $request): Response
    {
        $employee = $this->employeeOrAbort($request);

        return Inertia::render('ESS/Payslips', [
            'payrolls' => Payroll::query()
                ->where('employee_id', $employee->id)
                ->with('organization')
                ->orderByDesc('period')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function compliance(Request $request): Response
    {
        $employee = $this->employeeOrAbort($request);

        return Inertia::render('ESS/Compliance', [
            'certificates' => $employee->certificates()->orderBy('expiry_date')->get(),
            'medicalCheckups' => $employee->medicalCheckups()->orderByDesc('checkup_date')->get(),
        ]);
    }

    private function employeeOrAbort(Request $request): \App\Models\Employee
    {
        $employee = $request->user()?->employee;

        abort_unless($employee, 403, 'Akun ini belum terhubung ke profil karyawan.');

        return $employee;
    }
}
