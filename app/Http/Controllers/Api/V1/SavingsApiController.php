<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CooperativeContributionType;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsApiController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('manage_cooperative_dues') || $request->user()?->can('view_cooperative_member'), 403);

        return response()->json([
            'data' => CooperativeContributionType::query()
                ->where('is_active', true)
                ->orderByRaw("CASE category WHEN 'POKOK' THEN 1 WHEN 'WAJIB' THEN 2 WHEN 'SUKARELA' THEN 3 WHEN 'KHUSUS' THEN 4 ELSE 99 END")
                ->orderBy('name')
                ->get()
                ->map(fn (CooperativeContributionType $type): array => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'category' => $type->category,
                    'default_amount' => (float) $type->default_amount,
                    'frequency' => $type->frequency,
                ]),
        ]);
    }

    public function ledger(Request $request, SavingsSummaryService $savingsSummary): JsonResponse
    {
        abort_unless($request->user()?->can('view_cooperative_ledger') || $request->user()?->can('manage_cooperative_dues'), 403);

        $filters = [
            ...$request->only([
                'member_id',
                'member_search',
                'category',
                'contribution_type_id',
                'ledger_scope',
                'entry_type',
                'start_date',
                'end_date',
            ]),
            'ledger_scope' => $request->input('ledger_scope', 'SAVINGS'),
        ];

        $entries = $savingsSummary->ledgerQuery(filters: $filters)
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $entries->through(fn ($entry): array => [
                'id' => $entry->id,
                'member' => [
                    'id' => $entry->member?->id,
                    'member_no' => $entry->member?->member_no,
                    'name' => $entry->member?->name,
                ],
                'entry_type' => $entry->entry_type,
                'ledger_scope' => $entry->ledger_scope,
                'category' => $entry->contributionType?->category ?? $entry->category_snapshot,
                'contribution_type' => $entry->contributionType ? [
                    'id' => $entry->contributionType->id,
                    'code' => $entry->contributionType->code,
                    'name' => $entry->contributionType->name,
                    'category' => $entry->contributionType->category,
                ] : null,
                'description' => $entry->description,
                'posted_at' => $entry->posted_at?->toDateString(),
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
            ]),
            'summary' => $savingsSummary->summary(filters: $filters),
        ]);
    }
}
