<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ProcessSavingsWithdrawalRequest;
use App\Models\SavingsWithdrawal;
use App\Services\Cooperative\SavingsWithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavingsWithdrawalController extends Controller
{
    public function __construct(private SavingsWithdrawalService $service) {}

    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        Gate::authorize('viewAny', SavingsWithdrawal::class);

        $withdrawals = SavingsWithdrawal::query()
            ->with(['member.user', 'member.organization'])
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');
        $scopeService->scopeVisibleTo($withdrawals, $request->user());
        $withdrawals = $withdrawals->paginate(20);

        return Inertia::render('Cooperative/Savings/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    public function process(ProcessSavingsWithdrawalRequest $request, SavingsWithdrawal $withdrawal): RedirectResponse
    {
        $decision = $request->validated('decision');

        if ($decision === 'APPROVE') {
            Gate::authorize('approve', $withdrawal);
            $this->service->approve($withdrawal, $request->user());
            $message = 'Penarikan simpanan disetujui dan diproses.';
        } else {
            Gate::authorize('approve', $withdrawal);
            $this->service->reject($withdrawal, $request->user(), $request->validated('rejection_reason'));
            $message = 'Pengajuan penarikan simpanan ditolak.';
        }

        return back()->with('success', $message);
    }
}
