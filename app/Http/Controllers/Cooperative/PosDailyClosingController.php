<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ClosePosDayRequest;
use App\Services\Cooperative\PosDailyClosingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosDailyClosingController extends Controller
{
    public function __construct(
        private PosDailyClosingService $service,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $date = request()->input('date', now()->toDateString());
        $validated = request()->validate(['organization_id' => ['nullable', 'uuid']]);
        $targetOrgId = $this->service->resolveClosingOrganization($user, $validated['organization_id'] ?? null);

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
        $closing = $this->service->closeDay($date, $user, $validated['organization_id'] ?? null);

        return to_route('cooperative.pos.closings.index', ['date' => $date, 'organization_id' => $closing->organization_id])
            ->with('success', "Closing harian {$date} berhasil.");
    }
}
