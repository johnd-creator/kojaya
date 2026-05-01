<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ConsolidatedReportService
{
    /**
     * Get consolidated employee statistics across all organizations.
     *
     * @return array{total_employees: int, by_organization: \Illuminate\Support\Collection}
     */
    public function getEmployeeStats(): array
    {
        $organizations = Organization::with('users')->get();

        return [
            'total_employees' => Employee::count(),
            'by_organization' => $organizations->map(fn (Organization $org) => [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'organization_code' => $org->code,
                'employee_count' => $org->users()->count(),
            ]),
        ];
    }

    /**
     * Get consolidated payroll summary across all organizations.
     *
     * @param  string  $periodFrom  Start period (Y-m format)
     * @param  string  $periodTo  End period (Y-m format)
     * @return array<int, array<string, mixed>>
     */
    public function getPayrollSummary(string $periodFrom, string $periodTo): array
    {
        return Payroll::whereBetween('period', [$periodFrom, $periodTo])
            ->selectRaw('
                organization_id,
                COUNT(*) as employee_count,
                SUM(gross_salary) as total_gross,
                SUM(net_salary) as total_net,
                MIN(period) as period_from,
                MAX(period) as period_to
            ')
            ->with('organization')
            ->groupBy('organization_id')
            ->get()
            ->map(fn ($payroll) => [
                'organization_id' => $payroll->organization_id,
                'organization_name' => $payroll->organization?->name ?? 'Unknown',
                'employee_count' => (int) $payroll->employee_count,
                'total_gross' => (float) $payroll->total_gross,
                'total_net' => (float) $payroll->total_net,
                'period_from' => $payroll->period_from,
                'period_to' => $payroll->period_to,
            ])
            ->toArray();
    }

    /**
     * Get organization hierarchy tree.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationTree(): array
    {
        $headOffice = Organization::where('type', 'HEAD_OFFICE')
            ->with('children.children')
            ->first();

        if (! $headOffice) {
            return [];
        }

        return $this->buildTree($headOffice);
    }

    /**
     * Recursively build organization tree structure.
     *
     * @param  Organization  $organization
     * @return array<string, mixed>
     */
    protected function buildTree(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'code' => $organization->code,
            'type' => $organization->type,
            'level' => $organization->level,
            'is_active' => $organization->is_active,
            'children' => $organization->children->map(fn ($child) => $this->buildTree($child)),
        ];
    }

    /**
     * Get consolidated statistics for a specific organization and its children.
     *
     * @param  string  $organizationId
     * @return array<string, mixed>
     */
    public function getOrganizationHierarchyStats(string $organizationId): array
    {
        $organization = Organization::with(['children', 'users'])->findOrFail($organizationId);

        $childIds = $organization->children->pluck('id')->toArray();
        $allIds = [$organizationId, ...$childIds];

        $employees = Employee::whereIn('organization_id', $allIds)->get();

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
                'type' => $organization->type,
                'level' => $organization->level,
            ],
            'stats' => [
                'direct_employees' => $organization->users->count(),
                'total_employees_including_children' => $employees->count(),
                'child_organizations_count' => $organization->children->count(),
            ],
            'children' => $organization->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'code' => $child->code,
                'employee_count' => $child->users->count(),
            ]),
        ];
    }

    /**
     * Get consolidated attendance statistics across organizations.
     *
     * @param  string  $month  Month in Y-m format
     * @return array<string, mixed>
     */
    public function getAttendanceStats(string $month): array
    {
        $startDate = $month.'-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $stats = DB::table('attendances')
            ->selectRaw('
                organization_id,
                COUNT(DISTINCT employee_id) as total_employees,
                SUM(CASE WHEN check_in_time IS NOT NULL THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN check_in_time IS NULL THEN 1 ELSE 0 END) as total_absent
            ')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('organization_id')
            ->get();

        return [
            'period' => $month,
            'by_organization' => $stats->map(fn ($stat) => [
                'organization_id' => $stat->organization_id,
                'total_employees' => (int) $stat->total_employees,
                'total_present' => (int) $stat->total_present,
                'total_absent' => (int) $stat->total_absent,
            ]),
        ];
    }
}
