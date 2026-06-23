<?php

namespace App\Http\Controllers\Cooperative;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\PostOpeningBalanceRequest;
use App\Http\Requests\Cooperative\PreviewOpeningBalanceRequest;
use App\Http\Requests\Cooperative\StoreOpeningBalanceDraftRequest;
use App\Http\Requests\Cooperative\VoidOpeningBalanceRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Services\Cooperative\CooperativeOpeningBalanceWizardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeOpeningBalanceWizardController extends Controller
{
    public function __construct(private readonly CooperativeOpeningBalanceWizardService $service) {}

    public function show(Request $request, CooperativeMember $member): Response
    {
        $this->authorizeWizardAccess($request, 'manage_cooperative_opening_balance');

        $member->load([
            'organization',
            'openingBalanceBatches.lines.contributionType',
        ]);

        return Inertia::render('Cooperative/Members/OpeningBalance/Wizard', [
            'member' => [
                'id' => $member->id,
                'no_anggota' => $member->no_anggota ?: $member->member_no,
                'nama_anggota' => $member->nama_anggota ?: $member->name,
                'tanggal_aktif' => optional($member->tanggal_aktif)->toDateString(),
                'status' => $member->status,
                'organization_id' => $member->organization_id,
                'organization_name' => $member->organization?->name,
            ],
            'contribution_types' => CooperativeContributionType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'category', 'default_amount', 'frequency'])
                ->map(fn (CooperativeContributionType $type) => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'category' => $type->category,
                    'default_amount' => (float) $type->default_amount,
                    'frequency' => $type->frequency,
                ]),
            'source_types' => CooperativeOpeningBalanceWizardService::SOURCE_TYPES,
            'history' => $member->openingBalanceBatches
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (CooperativeMemberOpeningBalanceBatch $batch) => [
                    'id' => $batch->id,
                    'status' => $batch->status->value,
                    'status_label' => $batch->status->label(),
                    'status_tone' => $batch->status->tone(),
                    'total_amount' => (float) $batch->total_amount,
                    'months_count' => $batch->months_count,
                    'period_start' => optional($batch->calculation_start_period)->toDateString(),
                    'period_end' => optional($batch->calculation_end_period)->toDateString(),
                    'source_type' => $batch->source_type,
                    'source_reference' => $batch->source_reference,
                    'source_document_date' => optional($batch->source_document_date)->toDateString(),
                    'notes' => $batch->notes,
                    'posted_at' => optional($batch->posted_at)->toDateTimeString(),
                    'posted_by' => $batch->posted_by,
                    'voided_at' => optional($batch->voided_at)->toDateTimeString(),
                    'void_reason' => $batch->void_reason,
                    'lines' => $batch->lines->map(fn ($line) => [
                        'id' => $line->id,
                        'category' => $line->category_snapshot,
                        'contribution_type' => $line->contributionType?->name,
                        'months_count' => $line->months_count,
                        'unit_amount' => (float) $line->unit_amount,
                        'total_amount' => (float) $line->total_amount,
                        'calculation_method' => $line->calculation_method,
                        'override_reason' => $line->override_reason,
                    ]),
                ]),
            'capabilities' => [
                'can_post' => $request->user()?->can('approve_cooperative_opening_balance') ?? false,
                'can_void' => $request->user()?->can('void_cooperative_opening_balance') ?? false,
            ],
            'default_period' => [
                'start' => optional($member->tanggal_aktif)->startOfMonth()?->toDateString(),
                'end' => now()->subMonth()->endOfMonth()->toDateString(),
            ],
        ]);
    }

    public function preview(PreviewOpeningBalanceRequest $request, CooperativeMember $member): \Illuminate\Http\JsonResponse
    {
        $preview = $this->service->preview($member, $request->validated());

        return response()->json([
            'preview' => $preview,
        ]);
    }

    public function store(StoreOpeningBalanceDraftRequest $request, CooperativeMember $member): RedirectResponse
    {
        $organization = $member->organization;

        if (! $organization) {
            abort(422, 'Anggota belum terhubung ke organisasi.');
        }

        $batch = $this->service->createDraft(
            $member,
            $request->validated(),
            $request->user(),
            $organization
        );

        return redirect()
            ->route('cooperative.members.opening-balance.show', $member)
            ->with('success', 'Draft saldo awal berhasil disimpan sebagai '.$batch->status->label().'.');
    }

    public function post(PostOpeningBalanceRequest $request, CooperativeMemberOpeningBalanceBatch $batch): RedirectResponse
    {
        abort_unless($batch->status === OpeningBalanceBatchStatus::Draft, 422, 'Batch sudah diproses.');

        $this->service->post($batch->fresh(), $request->user());

        return redirect()
            ->route('cooperative.members.opening-balance.show', $batch->cooperative_member_id)
            ->with('success', 'Saldo awal berhasil diposting ke ledger simpanan.');
    }

    public function void(VoidOpeningBalanceRequest $request, CooperativeMemberOpeningBalanceBatch $batch): RedirectResponse
    {
        abort_unless($batch->status === OpeningBalanceBatchStatus::Posted, 422, 'Hanya batch berstatus POSTED yang bisa di-void.');

        $this->service->void($batch->fresh(), $request->user(), $request->string('reason')->toString());

        return redirect()
            ->route('cooperative.members.opening-balance.show', $batch->cooperative_member_id)
            ->with('success', 'Saldo awal berhasil di-void dan di-reverse di ledger.');
    }

    private function authorizeWizardAccess(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission) ?? false, 403, 'Anda tidak memiliki akses ke wizard saldo awal.');
    }
}
