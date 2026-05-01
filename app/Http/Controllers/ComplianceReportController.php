<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplianceReportController extends Controller
{
    public function certificateCompliance(Request $request): JsonResponse
    {
        $query = EmployeeCertificate::query()
            ->with('employee.organization');

        // Filter by organization if not system admin
        if ($request->user()->organization_id) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('organization_id', $request->user()->organization_id);
            });
        }

        $total = $query->count();
        $valid = (clone $query)->valid()->count();
        $expiring = (clone $query)->expiring()->count();
        $expired = (clone $query)->expired()->count();

        $complianceRate = $total > 0 ? round(($valid / $total) * 100, 2) : 0;

        $expiringCertificates = (clone $query)->expiring()
            ->with('employee')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total' => $total,
                'valid' => $valid,
                'expiring' => $expiring,
                'expired' => $expired,
                'compliance_rate' => $complianceRate,
            ],
            'expiring_soon' => EmployeeCertificateResource::collection($expiringCertificates),
        ]);
    }

    public function mcuCompliance(Request $request): JsonResponse
    {
        $query = MedicalCheckup::query()
            ->with('employee.organization');

        // Filter by organization if not system admin
        if ($request->user()->organization_id) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('organization_id', $request->user()->organization_id);
            });
        }

        $total = $query->count();
        $due = (clone $query)->due()->count();
        $overdue = (clone $query)->where('next_checkup_date', '<', now())->count();

        $upToDate = $total - $due;

        $complianceRate = $total > 0 ? round(($upToDate / $total) * 100, 2) : 0;

        $dueForCheckup = (clone $query)->due()
            ->with('employee')
            ->orderBy('next_checkup_date')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total' => $total,
                'up_to_date' => $upToDate,
                'due' => $due,
                'overdue' => $overdue,
                'compliance_rate' => $complianceRate,
            ],
            'due_soon' => MedicalCheckupResource::collection($dueForCheckup),
        ]);
    }

    public function nonCompliantEmployees(Request $request): JsonResponse
    {
        $query = DB::table('employees as e')
            ->leftJoin('employee_certificates as ec', function ($join) {
                $join->on('e.id', '=', 'ec.employee_id')
                    ->where('ec.status', '=', 'VALID')
                    ->where('ec.expiry_date', '>', now());
            })
            ->leftJoin('medical_checkups as mc', function ($join) {
                $join->on('e.id', '=', 'mc.employee_id')
                    ->where('mc.next_checkup_date', '>', now());
            })
            ->select(
                'e.id',
                'e.first_name',
                'e.last_name',
                'e.employee_code',
                DB::raw('COUNT(DISTINCT ec.id) as valid_certificates'),
                DB::raw('MAX(mc.next_checkup_date) as next_mcu_date')
            )
            ->groupBy('e.id', 'e.first_name', 'e.last_name', 'e.employee_code')
            ->havingRaw('valid_certificates = 0 OR next_mcu_date IS NULL OR next_mcu_date < NOW()');

        // Filter by organization if not system admin
        if ($request->user()->organization_id) {
            $query->where('e.organization_id', $request->user()->organization_id);
        }

        $nonCompliant = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $nonCompliant,
        ]);
    }
}
