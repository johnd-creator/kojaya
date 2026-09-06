<?php

namespace App\Http\Controllers\Cooperative;

use App\Enums\OrganizationVisibilityState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ClosePosDayRequest;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\PosDailyClosingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosDailyClosingController extends Controller
{
    public function __construct(
        private PosDailyClosingService $service,
        private OrganizationScopeService $scopeService,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $date = request()->input('date', now()->toDateString());
        $targetOrgId = $this->resolveTargetOrganization($user, request()->input('organization_id'));

        return Inertia::render('Cooperative/Pos/Closings/Index', [
            'date' => $date,
            'organization_id' => $targetOrgId,
            'summary' => $this->service->summaryForDate($date, $targetOrgId),
            'payment_summary' => $this->service->paymentSummaryForDate($date, $targetOrgId),
            'member_credit_outstanding' => $this->service->memberCreditOutstanding($targetOrgId),
            'is_locked' => $this->service->isLocked($date, $targetOrgId),
        ]);
    }

    public function close(ClosePosDayRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $validated = $request->validated();
        $date = $validated['date'];
        $targetOrgId = $this->resolveTargetOrganization($user, $validated['organization_id'] ?? null);

        $this->service->closeDay($date, $user, $targetOrgId);

        return to_route('cooperative.pos.closings.index', ['date' => $date, 'organization_id' => $targetOrgId])
            ->with('success', "Closing harian {$date} berhasil.");
    }

    private function resolveTargetOrganization(User $user, ?string $requestedOrgId = null): string
    {
        $visibility = $this->scopeService->visibilityFor($user, 'view_cooperative_all');

        if ($visibility->state === OrganizationVisibilityState::DENIED) {
            abort(403, 'Pengguna tanpa organisasi tidak diizinkan mengakses closing POS.');
        }

        if (! $visibility->global) {
            if ($requestedOrgId !== null && $requestedOrgId !== (string) $visibility->organizationId) {
                abort(403, 'Pengguna tidak diizinkan mengakses organisasi lain.');
            }

            return (string) $visibility->organizationId;
        }

        $targetOrgId = $requestedOrgId ?? session('active_organization_id') ?? $user->organization_id;
        if (empty($targetOrgId)) {
            abort(403, 'Target organisasi wajib ditentukan untuk pengguna global.');
        }

        try {
            return $this->scopeService->assertOrganizationIdentifier($targetOrgId);
        } catch (\Throwable) {
            abort(422, 'Organisasi target tidak ditemukan.');
        }
    }
}
