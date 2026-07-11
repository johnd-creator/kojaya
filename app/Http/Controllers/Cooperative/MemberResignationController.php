<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ProcessMemberResignationRequest;
use App\Models\MemberResignationRequest;
use App\Services\Cooperative\MemberResignationRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberResignationController extends Controller
{
    public function __construct(private readonly MemberResignationRequestService $service) {}

    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        Gate::authorize('viewAny', MemberResignationRequest::class);

        $baseQuery = MemberResignationRequest::query()
            ->with(['member.organization', 'reviewer']);

        // Scope by organization through the member relation.
        if (! $scopeService->canViewAllOrganizations($request->user())) {
            $baseQuery->whereHas('member', function ($memberQuery) use ($request): void {
                $memberQuery->where('organization_id', $request->user()->organization_id);
            });
        }

        $query = (clone $baseQuery)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = $request->string('search')->toString();
                $q->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%")
                        ->orWhere('no_anggota', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'pending' => (int) ($statusCounts['PENDING'] ?? 0),
            'approved' => (int) ($statusCounts['APPROVED'] ?? 0),
            'rejected' => (int) ($statusCounts['REJECTED'] ?? 0),
            'cancelled' => (int) ($statusCounts['CANCELLED'] ?? 0),
            'total' => (int) $statusCounts->sum(),
        ];

        return Inertia::render('Cooperative/Members/Resignations/Index', [
            'requests' => $query,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function process(ProcessMemberResignationRequest $request, MemberResignationRequest $resignationRequest): RedirectResponse
    {
        Gate::authorize('approve', $resignationRequest);

        $decision = $request->validated('decision');

        try {
            if ($decision === 'APPROVE') {
                $this->service->approve($resignationRequest, $request->user(), $request->validated('review_notes'));
                $message = 'Pengunduran diri disetujui. Status anggota diperbarui menjadi RESIGNED.';
            } else {
                $this->service->reject($resignationRequest, $request->user(), $request->validated('review_notes'));
                $message = 'Pengajuan pengunduran diri ditolak.';
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->with('error', collect($e->validator->errors()->all())->join(' '))
                ->withInput();
        }

        return back()->with('success', $message);
    }
}
