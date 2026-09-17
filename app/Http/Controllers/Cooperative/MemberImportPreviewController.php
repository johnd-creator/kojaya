<?php

namespace App\Http\Controllers\Cooperative;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\PreviewMemberImportRequest;
use App\Models\Organization;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\MemberImportValidator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberImportPreviewController extends Controller
{
    /**
     * Render the member import dry-run / preview page.
     */
    public function index(Request $request, OrganizationScopeService $scopeService): Response
    {
        $user = $request->user();
        abort_unless($user && $user->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value), 403);

        $visibility = $scopeService->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $isGlobal = $visibility->global;

        if ($isGlobal) {
            $organizations = Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Organization $org): array => [
                    'id' => (string) $org->id,
                    'code' => $org->code,
                    'name' => $org->name,
                ])
                ->all();
            $currentOrganizationId = null;
        } else {
            $currentOrg = Organization::query()->find($visibility->organizationId);
            $organizations = $currentOrg ? [[
                'id' => (string) $currentOrg->id,
                'code' => $currentOrg->code,
                'name' => $currentOrg->name,
            ]] : [];
            $currentOrganizationId = (string) $visibility->organizationId;
        }

        $defaultImportDate = now()->toDateString();

        return Inertia::render('Cooperative/Members/ImportPreview', [
            'is_global' => $isGlobal,
            'current_organization_id' => $currentOrganizationId,
            'organizations' => $organizations,
            'default_import_date' => $defaultImportDate,
            'canonical_headers' => MemberImportValidator::CANONICAL_HEADERS,
            'preview' => null,
        ]);
    }

    /**
     * Validate the uploaded CSV and return a dry-run preview result.
     * No data is written to the database.
     */
    public function preview(
        PreviewMemberImportRequest $request,
        OrganizationScopeService $scopeService,
        MemberImportValidator $validator,
    ): Response {
        $user = $request->user();
        abort_unless($user && $user->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value), 403);

        $targetOrgId = $request->input('organization_id');
        $authorizedOrganizationId = $scopeService->resolveTargetOrganization(
            $user,
            $targetOrgId,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        );

        $importDate = $request->validated('import_date');
        $uploadedFile = $request->file('file');

        // Execute deterministic validation without permanently storing the file
        $result = $validator->validateFile(
            $uploadedFile->getRealPath(),
            [
                'organization_id' => $authorizedOrganizationId,
                'import_date' => $importDate,
            ],
        );

        // Safe serialized representation:
        // ImportValidationResult::toArray() ensures raw_data and normalized_data NIK are [REDACTED]
        $previewData = $result->toArray();

        $visibility = $scopeService->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $isGlobal = $visibility->global;

        $organizations = $isGlobal
            ? Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Organization $org): array => [
                    'id' => (string) $org->id,
                    'code' => $org->code,
                    'name' => $org->name,
                ])
                ->all()
            : (Organization::query()
                ->whereKey($authorizedOrganizationId)
                ->get(['id', 'code', 'name'])
                ->map(fn (Organization $org): array => [
                    'id' => (string) $org->id,
                    'code' => $org->code,
                    'name' => $org->name,
                ])
                ->all());

        return Inertia::render('Cooperative/Members/ImportPreview', [
            'is_global' => $isGlobal,
            'current_organization_id' => $authorizedOrganizationId,
            'organizations' => $organizations,
            'default_import_date' => $importDate,
            'canonical_headers' => MemberImportValidator::CANONICAL_HEADERS,
            'preview' => $previewData,
        ]);
    }

    /**
     * Download the canonical 12-column template CSV.
     */
    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value), 403);

        $templatePath = base_path('docs/onboarding/member-import-template.csv');
        abort_unless(file_exists($templatePath), 404, 'Template berkas impor tidak ditemukan.');

        return response()->download($templatePath, 'member-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
