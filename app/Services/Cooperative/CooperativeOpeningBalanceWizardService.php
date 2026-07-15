<?php

namespace App\Services\Cooperative;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\CooperativeMemberOpeningBalanceLine;
use App\Models\MemberOnboardingProgress;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Wizard saldo awal anggota: hitung periode, simpan draft batch + lines,
 * posting ke ledger simpanan, dan reversal via void.
 *
 * Kontrak ledger:
 *  - Entry type OPENING_BALANCE -> credit (saldo awal bertambah)
 *  - Entry type OPENING_BALANCE_REVERSAL -> debit (koreksi saldo)
 *  - Source polymorphic ke CooperativeMemberOpeningBalanceLine sehingga
 *    satu batch dengan banyak line tetap tunduk pada unique
 *    (source_type, source_id, entry_type) di cooperative_ledger_entries.
 *  - Ledger scope = SAVINGS
 *  - Period disimpan di metadata (calculation_start/end)
 */
class CooperativeOpeningBalanceWizardService
{
    public const CATEGORIES = ['POKOK', 'WAJIB', 'SUKARELA', 'KHUSUS'];

    public const SOURCE_TYPES = [
        'MIGRATION_LEDGER' => 'Migrasi dari buku lama',
        'MANUAL_RECONCILIATION' => 'Rekonsiliasi saldo manual',
        'EXCEL_IMPORT' => 'Import dari Excel',
        'BOARD_DECISION' => 'Keputusan pengurus',
    ];

    public const CONFLICT_ENTRY_TYPES = ['SAVING_PAYMENT', 'OPENING_BALANCE'];

    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * Hitung jumlah bulan antara dua tanggal (inklusif bulan start, eksklusif bulan setelah end).
     * Mengembalikan 0 bila endDate lebih awal dari startDate (sebelum normalisasi).
     */
    public function monthsBetween(string $startDate, string $endDate): int
    {
        $start = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        if ($end->lt($start)) {
            return 0;
        }

        $start = $start->startOfMonth();
        $end = $end->startOfMonth();

        return ((int) $start->diffInMonths($end)) + 1;
    }

    /**
     * Bangun struktur kalkulasi preview dari input wizard tanpa menyimpan ke DB.
     *
     * Periode `calculation_start_period`/`calculation_end_period` dinormalisasi
     * ke awal/akhir bulan sebelum dipakai membangun line, sehingga dokumen,
     * UI, dan DB semuanya konsisten (`YYYY-MM-DD` = `YYYY-MM-01` atau
     * `YYYY-MM-last-day`).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function preview(CooperativeMember $member, array $input): array
    {
        $rawStart = (string) ($input['calculation_start_period'] ?? $member->tanggal_aktif?->toDateString() ?? '');
        $rawEnd = (string) ($input['calculation_end_period'] ?? now()->subMonth()->endOfMonth()->toDateString());
        $includeCurrentMonth = (bool) ($input['include_current_month'] ?? false);

        if ($includeCurrentMonth) {
            $rawEnd = now()->endOfMonth()->toDateString();
        }

        $start = CarbonImmutable::parse($rawStart)->startOfMonth()->toDateString();
        $end = CarbonImmutable::parse($rawEnd)->endOfMonth()->toDateString();

        $months = $this->monthsBetween($start, $end);

        $selectedTypes = $this->resolveContributionTypes($input['contribution_types'] ?? []);
        $lines = $this->buildLines($selectedTypes, $months, $start, $end, $input['overrides'] ?? []);

        $total = array_sum(array_map(fn (array $line) => (float) $line['total_amount'], $lines));

        $conflicts = $this->detectExistingMutationConflicts($member, $lines);

        return [
            'calculation_start_period' => $start,
            'calculation_end_period' => $end,
            'months_count' => $months,
            'lines' => $lines,
            'total_amount' => round($total, 2),
            'conflicts' => $conflicts,
            'has_conflicts' => $conflicts !== [],
        ];
    }

    /**
     * Deteksi ledger simpanan (SAVING_PAYMENT / OPENING_BALANCE) yang sudah
     * ada untuk kategori dan/atau periode yang akan dicakup wizard.
     *
     * Hasil dipakai UI preview sebagai peringatan agar admin tahu ada risiko
     * saldo ganda sebelum finalisasi.
     *
     * Catatan: `category_snapshot` null atau `cooperative_contribution_type_id`
     * null pada entry legacy `OPENING_BALANCE` (yang dibuat oleh
     * `CooperativeOpeningBalanceService` lama) tetap dideteksi sebagai
     * warning global agar admin tidak melewatkan saldo awal historis
     * yang sudah terlanjur tercatat tanpa kategori.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function detectExistingMutationConflicts(CooperativeMember $member, array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $categories = array_values(array_unique(array_map(
            fn (array $line) => (string) $line['category_snapshot'],
            $lines
        )));

        $contributionTypeIds = array_values(array_unique(array_filter(array_map(
            fn (array $line) => $line['cooperative_contribution_type_id'] ?? null,
            $lines
        ))));

        $typeCategoryPairs = CooperativeContributionType::query()
            ->whereIn('id', $contributionTypeIds)
            ->get(['id', 'category'])
            ->mapWithKeys(fn ($type) => [(int) $type->id => (string) $type->category])
            ->all();

        $categoryByType = [];
        foreach ($typeCategoryPairs as $typeId => $cat) {
            $categoryByType[$typeId] = $cat;
        }

        $categoriesToQuery = array_values(array_unique(array_merge(
            $categories,
            array_values($categoryByType)
        )));

        $query = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->whereIn('entry_type', self::CONFLICT_ENTRY_TYPES)
            ->where(function ($q) use ($categoriesToQuery, $contributionTypeIds): void {
                $q->whereIn('category_snapshot', $categoriesToQuery)
                    ->orWhereIn('cooperative_contribution_type_id', $contributionTypeIds)
                    ->orWhere(function ($inner): void {
                        $inner->whereNull('category_snapshot')
                            ->whereNull('cooperative_contribution_type_id')
                            ->where('entry_type', 'OPENING_BALANCE');
                    });
            });

        $entries = $query
            ->orderBy('posted_at')
            ->get(['id', 'category_snapshot', 'cooperative_contribution_type_id', 'entry_type', 'period', 'credit', 'debit', 'posted_at', 'description']);

        if ($entries->isEmpty()) {
            return [];
        }

        $conflicts = [];
        $periodRanges = [];

        foreach ($lines as $line) {
            if (! empty($line['period_start']) && ! empty($line['period_end'])) {
                $periodRanges[] = [
                    'category' => (string) $line['category_snapshot'],
                    'start' => CarbonImmutable::parse((string) $line['period_start'])->startOfMonth(),
                    'end' => CarbonImmutable::parse((string) $line['period_end'])->startOfMonth(),
                ];
            }
        }

        foreach ($entries as $entry) {
            $effectiveCategory = $entry->category_snapshot
                ?? ($entry->cooperative_contribution_type_id !== null
                    ? ($categoryByType[(int) $entry->cooperative_contribution_type_id] ?? null)
                    : null);

            $entryPeriod = $entry->period ? CarbonImmutable::createFromFormat('Y-m', (string) $entry->period)->startOfMonth() : null;
            $overlapMonths = null;
            $matchedRange = false;

            if ($entryPeriod !== null) {
                foreach ($periodRanges as $range) {
                    if ($range['category'] !== $effectiveCategory) {
                        continue;
                    }
                    if ($entryPeriod->betweenIncluded($range['start'], $range['end'])) {
                        $matchedRange = true;
                        $overlapMonths = (int) $range['start']->diffInMonths($entryPeriod) + 1;
                        break;
                    }
                }
            }

            $isLegacyUncategorized = $entry->entry_type === 'OPENING_BALANCE'
                && $entry->category_snapshot === null
                && $entry->cooperative_contribution_type_id === null;

            $conflicts[] = [
                'category' => $effectiveCategory,
                'entry_type' => $entry->entry_type,
                'period' => $entry->period,
                'posted_at' => optional($entry->posted_at)->toDateString(),
                'amount' => (float) $entry->credit - (float) $entry->debit,
                'description' => $entry->description,
                'overlaps_calculation_period' => $matchedRange,
                'overlap_month_label' => $overlapMonths !== null
                    ? sprintf('%s (bulan ke-%d)', $entryPeriod?->format('Y-m'), $overlapMonths)
                    : null,
                'is_legacy_uncategorized' => $isLegacyUncategorized,
                'message' => $isLegacyUncategorized
                    ? 'Saldo awal legacy tanpa kategori terdeteksi untuk anggota ini. Wizard tidak akan menimpa entry ini; lakukan koreksi lewat reversal/void jika perlu.'
                    : ($matchedRange
                        ? "Mutasi {$entry->entry_type} untuk kategori {$entry->category_snapshot} periode {$entry->period} sudah ada dan tumpang tindih dengan periode kalkulasi wizard."
                        : "Mutasi {$entry->entry_type} untuk kategori {$entry->category_snapshot} sudah pernah dicatat. Pastikan tidak terjadi saldo ganda."),
            ];
        }

        return $conflicts;
    }

    /**
     * Buat batch DRAFT dari input wizard.
     *
     * @param  array<string, mixed>  $input
     */
    public function createDraft(CooperativeMember $member, array $input, User $creator, Organization $organization): CooperativeMemberOpeningBalanceBatch
    {
        $this->assertMemberEligible($member);

        $preview = $this->preview($member, $input);

        if ($preview['total_amount'] <= 0) {
            throw new RuntimeException('Total saldo awal harus lebih besar dari 0.');
        }

        if (empty($preview['lines'])) {
            throw new RuntimeException('Pilih minimal satu jenis simpanan.');
        }

        return DB::transaction(function () use ($member, $organization, $creator, $preview, $input): CooperativeMemberOpeningBalanceBatch {
            $batch = CooperativeMemberOpeningBalanceBatch::query()->create([
                'cooperative_member_id' => $member->id,
                'organization_id' => $organization->id,
                'status' => OpeningBalanceBatchStatus::Draft,
                'calculation_start_period' => $preview['calculation_start_period'],
                'calculation_end_period' => $preview['calculation_end_period'],
                'months_count' => $preview['months_count'],
                'total_amount' => $preview['total_amount'],
                'source_type' => $input['source_type'] ?? null,
                'source_reference' => $input['source_reference'] ?? null,
                'source_document_date' => $input['source_document_date'] ?? null,
                'notes' => $input['notes'] ?? null,
                'metadata' => [
                    'creator_id' => $creator->id,
                    'include_current_month' => (bool) ($input['include_current_month'] ?? false),
                ],
            ]);

            foreach ($preview['lines'] as $line) {
                CooperativeMemberOpeningBalanceLine::query()->create([
                    'opening_balance_batch_id' => $batch->id,
                    'cooperative_contribution_type_id' => $line['cooperative_contribution_type_id'],
                    'category_snapshot' => $line['category_snapshot'],
                    'period_start' => $line['period_start'],
                    'period_end' => $line['period_end'],
                    'months_count' => $line['months_count'],
                    'unit_amount' => $line['unit_amount'],
                    'total_amount' => $line['total_amount'],
                    'calculation_method' => $line['calculation_method'],
                    'override_reason' => $line['override_reason'],
                    'metadata' => $line['metadata'] ?? null,
                ]);
            }

            $batch->refresh()->load('lines');

            $this->writeAuditLog($creator, $batch, 'opening_balance.draft_created', [
                'total_amount' => (float) $batch->total_amount,
                'months_count' => $batch->months_count,
                'source_type' => $batch->source_type,
                'has_conflicts' => $preview['has_conflicts'] ?? false,
            ]);

            return $batch;
        });
    }

    /**
     * Finalisasi batch: posting ledger simpanan per line.
     */
    public function post(CooperativeMemberOpeningBalanceBatch $batch, User $poster): CooperativeMemberOpeningBalanceBatch
    {
        if (! $batch->isDraft()) {
            throw new RuntimeException('Hanya batch DRAFT yang dapat difinalisasi.');
        }

        $this->assertNoPostedDuplicatePokok($batch);

        return DB::transaction(function () use ($batch, $poster): CooperativeMemberOpeningBalanceBatch {
            $batch->load(['member', 'lines.contributionType']);

            $postedAt = now();
            $postedLineIds = [];

            foreach ($batch->lines as $line) {
                $amount = (float) $line->total_amount;
                if ($amount <= 0) {
                    continue;
                }

                $entry = CooperativeLedgerEntry::query()->create([
                    'cooperative_member_id' => $batch->cooperative_member_id,
                    'organization_id' => $batch->organization_id,
                    'ledger_scope' => 'SAVINGS',
                    'entry_type' => 'OPENING_BALANCE',
                    'cooperative_contribution_type_id' => $line->cooperative_contribution_type_id,
                    'category_snapshot' => $line->category_snapshot,
                    'source_type' => CooperativeMemberOpeningBalanceLine::class,
                    'source_id' => $line->id,
                    'period' => $line->period_start?->format('Y-m'),
                    'description' => $this->buildDescription($batch, $line),
                    'debit' => 0,
                    'credit' => $amount,
                    'posted_at' => $postedAt,
                    'metadata' => [
                        'opening_balance_batch_id' => $batch->id,
                        'opening_balance_line_id' => $line->id,
                        'months_count' => $line->months_count,
                        'period_start' => $line->period_start?->toDateString(),
                        'period_end' => $line->period_end?->toDateString(),
                    ],
                ]);

                $postedLineIds[] = $entry->id;
            }

            $batch->forceFill([
                'status' => OpeningBalanceBatchStatus::Posted,
                'posted_by' => $poster->id,
                'posted_at' => $postedAt,
            ])->save();

            $this->markOnboardingFirstSavingsPaid($batch);
            $this->writeAuditLog($poster, $batch->refresh(), 'opening_balance.posted', [
                'line_ids' => $postedLineIds,
                'total_amount' => (float) $batch->total_amount,
                'months_count' => $batch->months_count,
            ]);

            return $batch->refresh()->load('lines');
        });
    }

    /**
     * Tandai step onboarding first_savings_paid_at anggota sebagai selesai
     * bila saldo awal pertama berhasil diposting. Idempotent: tidak menimpa
     * nilai yang sudah ada.
     */
    private function markOnboardingFirstSavingsPaid(CooperativeMemberOpeningBalanceBatch $batch): void
    {
        if ((float) $batch->total_amount <= 0) {
            return;
        }

        $progress = MemberOnboardingProgress::query()->firstOrCreate([
            'cooperative_member_id' => $batch->cooperative_member_id,
        ]);

        if ($progress->first_savings_paid_at === null) {
            $progress->forceFill(['first_savings_paid_at' => $batch->posted_at ?? now()])->save();
        }
    }

    /**
     * Batalkan batch yang sudah diposting dengan membuat entry reversal.
     */
    public function void(CooperativeMemberOpeningBalanceBatch $batch, User $voider, string $reason): CooperativeMemberOpeningBalanceBatch
    {
        if (! $batch->canBeVoided()) {
            throw new RuntimeException('Hanya batch POSTED yang dapat dibatalkan.');
        }

        if (trim($reason) === '') {
            throw new RuntimeException('Alasan pembatalan wajib diisi.');
        }

        return DB::transaction(function () use ($batch, $voider, $reason): CooperativeMemberOpeningBalanceBatch {
            $batch->load(['lines']);

            $existingEntries = CooperativeLedgerEntry::query()
                ->whereIn('source_type', [CooperativeMemberOpeningBalanceLine::class])
                ->whereIn('source_id', $batch->lines->pluck('id'))
                ->where('entry_type', 'OPENING_BALANCE')
                ->get();

            $now = now();

            foreach ($existingEntries as $entry) {
                $credit = (float) $entry->credit;
                if ($credit <= 0) {
                    continue;
                }

                CooperativeLedgerEntry::query()->create([
                    'cooperative_member_id' => $entry->cooperative_member_id,
                    'organization_id' => $entry->organization_id,
                    'ledger_scope' => $entry->ledger_scope,
                    'entry_type' => 'OPENING_BALANCE_REVERSAL',
                    'cooperative_contribution_type_id' => $entry->cooperative_contribution_type_id,
                    'category_snapshot' => $entry->category_snapshot,
                    'source_type' => $entry->source_type,
                    'source_id' => $entry->source_id,
                    'period' => $entry->period,
                    'description' => "Reversal saldo awal: {$reason}",
                    'debit' => $credit,
                    'credit' => 0,
                    'posted_at' => $now,
                    'metadata' => array_merge($entry->metadata ?? [], [
                        'reversal_of_entry_id' => $entry->id,
                        'void_reason' => $reason,
                    ]),
                ]);
            }

            $batch->forceFill([
                'status' => OpeningBalanceBatchStatus::Voided,
                'voided_by' => $voider->id,
                'voided_at' => $now,
                'void_reason' => $reason,
            ])->save();

            $this->writeAuditLog($voider, $batch->refresh(), 'opening_balance.voided', [
                'reversal_entry_count' => $existingEntries->count(),
                'void_reason' => $reason,
            ]);

            return $batch->refresh()->load('lines');
        });
    }

    /**
     * Tulis audit log untuk aksi wizard (draft, post, void). Dipakai agar
     * jejak audit tersimpan di AuditLogService terpusat, bukan hanya di
     * metadata batch.
     *
     * Kegagalan tulis audit **tidak** menggagalkan transaksi finansial
     * utama, tetapi di-report lewat exception handler agar monitoring
     * (Sentry/log aggregator) tetap melihat sinyal tersebut.
     *
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(
        User $actor,
        CooperativeMemberOpeningBalanceBatch $batch,
        string $action,
        array $newValues = []
    ): void {
        try {
            $this->auditLogService->logModelEvent($action, $batch, null, [
                'actor_id' => $actor->id,
                'cooperative_member_id' => $batch->cooperative_member_id,
                'status' => $batch->status->value,
                ...$newValues,
            ], AuditContext::forActor($actor));
        } catch (\Throwable $exception) {
            // Jangan gagalkan transaksi finansial utama, tapi laporkan agar
            // tim operasi punya visibilitas terhadap audit failure.
            report($exception);

            \Illuminate\Support\Facades\Log::warning('cooperative_opening_balance.audit_log_failed', [
                'action' => $action,
                'batch_id' => $batch->id,
                'actor_id' => $actor->id,
                'cooperative_member_id' => $batch->cooperative_member_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $selectedTypes
     * @return array<int, CooperativeContributionType>
     */
    private function resolveContributionTypes(array $selectedTypes): array
    {
        if (empty($selectedTypes)) {
            return [];
        }

        $ids = array_values(array_filter(array_map('intval', $selectedTypes)));

        return CooperativeContributionType::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * @param  array<int, CooperativeContributionType>  $types
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<int, array<string, mixed>>
     */
    private function buildLines(array $types, int $months, string $start, string $end, array $overrides): array
    {
        $lines = [];

        foreach ($types as $type) {
            $category = strtoupper((string) $type->category);
            if (! in_array($category, self::CATEGORIES, true)) {
                continue;
            }

            $override = $overrides[(string) $type->id] ?? [];
            $unitAmount = isset($override['unit_amount']) && $override['unit_amount'] !== ''
                ? (float) $override['unit_amount']
                : (float) $type->default_amount;
            $overrideReason = $override['reason'] ?? null;

            $calculationMethod = match ($category) {
                'POKOK' => 'ONCE',
                'SUKARELA', 'KHUSUS' => 'MANUAL',
                default => 'MONTHLY',
            };

            if ($calculationMethod === 'ONCE') {
                $monthsCount = 1;
                $total = round($unitAmount, 2);
                $periodStart = null;
                $periodEnd = null;
            } elseif ($calculationMethod === 'MANUAL') {
                $monthsCount = 0;
                $total = round($unitAmount, 2);
                $periodStart = null;
                $periodEnd = null;
            } else {
                $monthsCount = $months;
                $total = round($unitAmount * $months, 2);
                $periodStart = $start;
                $periodEnd = $end;
            }

            if ($total <= 0) {
                continue;
            }

            $lines[] = [
                'cooperative_contribution_type_id' => $type->id,
                'category_snapshot' => $category,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'months_count' => $monthsCount,
                'unit_amount' => $unitAmount,
                'total_amount' => $total,
                'calculation_method' => $calculationMethod,
                'override_reason' => $overrideReason,
                'metadata' => [
                    'contribution_code' => $type->code,
                    'contribution_name' => $type->name,
                ],
            ];
        }

        return $lines;
    }

    private function buildDescription(CooperativeMemberOpeningBalanceBatch $batch, CooperativeMemberOpeningBalanceLine $line): string
    {
        $category = $line->category_snapshot;
        $typeName = $line->contributionType?->name ?? $category;

        if ($line->calculation_method === 'ONCE') {
            return "Saldo awal {$typeName} (pokok)";
        }

        if ($line->calculation_method === 'MANUAL') {
            return "Saldo awal {$typeName} (manual)";
        }

        $periodLabel = sprintf(
            '%s s/d %s (%d bln)',
            $line->period_start?->format('Y-m') ?? '-',
            $line->period_end?->format('Y-m') ?? '-',
            $line->months_count,
        );

        return "Saldo awal {$typeName} {$periodLabel}";
    }

    private function assertMemberEligible(CooperativeMember $member): void
    {
        if ($member->status === 'RESIGNED') {
            throw new RuntimeException('Anggota berstatus RESIGNED tidak dapat memiliki saldo awal baru.');
        }

        if ($member->activeOpeningBalanceBatch() !== null) {
            throw new RuntimeException('Anggota sudah memiliki batch saldo awal berstatus POSTED. Gunakan koreksi/reversal.');
        }
    }

    private function assertNoPostedDuplicatePokok(CooperativeMemberOpeningBalanceBatch $batch): void
    {
        $hasPokok = $batch->lines->contains(fn (CooperativeMemberOpeningBalanceLine $line) => $line->category_snapshot === 'POKOK');

        if (! $hasPokok) {
            return;
        }

        // POKOK boleh diposting lagi HANYA bila semua entry POKOK OPENING_BALANCE
        // sebelumnya sudah diimbangi oleh OPENING_BALANCE_REVERSAL (lewat void).
        $existingEntries = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $batch->cooperative_member_id)
            ->where('category_snapshot', 'POKOK')
            ->where('entry_type', 'OPENING_BALANCE')
            ->get(['id']);

        if ($existingEntries->isEmpty()) {
            return;
        }

        $reversedIds = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $batch->cooperative_member_id)
            ->where('entry_type', 'OPENING_BALANCE_REVERSAL')
            ->get(['metadata'])
            ->map(function (CooperativeLedgerEntry $entry) {
                $metadata = $entry->metadata;

                if (is_string($metadata)) {
                    $decoded = json_decode($metadata, true);

                    return is_array($decoded) ? ($decoded['reversal_of_entry_id'] ?? null) : null;
                }

                if (is_array($metadata)) {
                    return $metadata['reversal_of_entry_id'] ?? null;
                }

                if (is_object($metadata) && isset($metadata->reversal_of_entry_id)) {
                    return $metadata->reversal_of_entry_id;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        $unreversed = $existingEntries->pluck('id')
            ->diff($reversedIds);

        if ($unreversed->isNotEmpty()) {
            throw new RuntimeException('Simpanan pokok saldo awal sudah pernah diposting untuk anggota ini dan belum di-reverse.');
        }
    }
}
