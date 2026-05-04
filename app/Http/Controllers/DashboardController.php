<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\ConsolidatedReportService;
use App\Services\Dashboard\CooperativeDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function show(CooperativeDashboardService $dashboard): InertiaResponse
    {
        return Inertia::render('Dashboard', [
            'dashboard' => Inertia::defer(fn () => $dashboard->data(), 'dashboard'),
        ]);
    }

    /**
     * Get dashboard data based on active organization context.
     *
     * If no active organization is set, returns consolidated data
     * across all organizations. Otherwise, returns data specific
     * to the selected organization.
     */
    public function index(Request $request, ConsolidatedReportService $service): JsonResponse
    {
        $activeOrgId = session('active_organization_id');

        // If no active organization, show consolidated data
        if (! $activeOrgId) {
            return Response::json([
                'view_mode' => 'consolidated',
                'message' => 'Showing consolidated data across all organizations',
                'stats' => $service->getEmployeeStats(),
                'organizations' => $service->getOrganizationTree(),
                'active_organization' => null,
            ]);
        }

        // Show specific organization data
        $organization = Organization::with('users')->findOrFail($activeOrgId);
        $orgStats = $service->getOrganizationHierarchyStats($activeOrgId);

        return Response::json([
            'view_mode' => 'organization',
            'message' => "Showing data for: {$organization->name}",
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
                'type' => $organization->type,
                'level' => $organization->level,
            ],
            'stats' => $orgStats['stats'],
            'children' => $orgStats['children'],
            'active_organization' => $activeOrgId,
        ]);
    }

    /**
     * Get available organizations for the switcher UI.
     */
    public function organizations(): JsonResponse
    {
        $organizations = Organization::query()
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'code' => $org->code,
                'type' => $org->type,
                'level' => $org->level,
            ]);

        return Response::json([
            'organizations' => $organizations,
            'active_organization_id' => session('active_organization_id'),
        ]);
    }
}
