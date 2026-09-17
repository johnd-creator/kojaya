<?php

namespace App\Http\Controllers\Cooperative;

use App\Enums\PermissionEnum;
use App\Exceptions\MemberImportExecutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ExecuteMemberImportRequest;
use App\Http\Requests\Cooperative\PreviewMemberImportRequest;
use App\Models\Organization;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\MemberImportExecutionService;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\PreviewProofService;
use App\Support\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'preview_proof' => null,
            'file_sha256' => null,
            'execution_enabled' => (bool) config('cooperative.member_import_execution_enabled', false),
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
        PreviewProofService $proofService,
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
        $fileSha256 = hash_file('sha256', $uploadedFile->getRealPath());

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

        // Issue tamper-resistant preview proof if batch is 100% ready for import
        $isReady = $result->valid
            && $result->headerValid
            && $result->totalRows > 0
            && $result->invalidRows === 0
            && ! $result->requiresManualReview();

        $previewProof = $isReady
            ? $proofService->generate($fileSha256, (string) $authorizedOrganizationId, $importDate)
            : null;

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
            'preview_proof' => $previewProof,
            'file_sha256' => $fileSha256,
            'execution_enabled' => (bool) config('cooperative.member_import_execution_enabled', false),
        ]);
    }

    /**
     * Execute transactional member import after server-side revalidation,
     * tamper-resistant preview proof verification, and DEV gate check.
     */
    public function execute(
        ExecuteMemberImportRequest $request,
        OrganizationScopeService $scopeService,
        PreviewProofService $proofService,
        MemberImportExecutionService $executionService,
    ): JsonResponse|RedirectResponse {
        $user = $request->user();
        abort_unless($user && $user->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value), 403);

        // DEV Execution Gate Check
        if (! config('cooperative.member_import_execution_enabled', false)) {
            abort(403, 'Eksekusi impor anggota saat ini dinonaktifkan.');
        }

        $targetOrgId = $request->input('organization_id');
        $authorizedOrganizationId = $scopeService->resolveTargetOrganization(
            $user,
            $targetOrgId,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        );

        $importDate = $request->validated('import_date');
        $uploadedFile = $request->file('file');
        $fileSha256 = hash_file('sha256', $uploadedFile->getRealPath());

        // Verify preview proof against uploaded file hash and parameters
        $proofResult = $proofService->verify(
            $request->validated('preview_proof'),
            $fileSha256,
            (string) $authorizedOrganizationId,
            $importDate,
        );

        if (! $proofResult['valid']) {
            throw ValidationException::withMessages([
                'preview_proof' => [$proofResult['message'] ?? 'Bukti pratinjau tidak valid.'],
            ]);
        }

        try {
            $result = $executionService->execute(
                filePath: $uploadedFile->getRealPath(),
                organizationId: (string) $authorizedOrganizationId,
                importDate: $importDate,
                fileSha256: $fileSha256,
                auditContext: AuditContext::fromCurrentRequest(),
            );
        } catch (MemberImportExecutionException $e) {
            throw ValidationException::withMessages([
                'execution' => [$e->getMessage()],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Import anggota ke DEV berhasil dipersistensikan.',
                'data' => $result->toArray(),
            ]);
        }

        return redirect()->route('cooperative.members.import')
            ->with('success', 'Import anggota ke DEV berhasil dipersistensikan.')
            ->with('import_result', $result->toArray());
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
