<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Cooperative\CooperativePeriodLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(private readonly CooperativePeriodLockService $periodLockService) {}

    public function create(array $data, User $user): JournalEntry
    {
        $lines = collect($data['lines'] ?? []);
        $totalDebit = (float) $lines->sum(fn (array $line): float => (float) ($line['debit'] ?? 0));
        $totalCredit = (float) $lines->sum(fn (array $line): float => (float) ($line['credit'] ?? 0));

        if ($lines->count() < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry must contain at least two lines.',
            ]);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry must be balanced.',
            ]);
        }

        $this->periodLockService->assertUnlocked(substr((string) $data['entry_date'], 0, 7), 'FINANCE');

        return DB::transaction(function () use ($data, $lines, $user): JournalEntry {
            $entry = JournalEntry::query()->create([
                'organization_id' => $data['organization_id'] ?? $user->organization_id,
                'posted_by_user_id' => $user->id,
                'journal_number' => $this->generateJournalNumber(),
                'entry_date' => $data['entry_date'],
                'status' => $data['status'] ?? 'POSTED',
                'reference_number' => $data['reference_number'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'description' => $data['description'],
            ]);

            $entry->lines()->createMany(
                $lines->map(fn (array $line): array => [
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ])->all()
            );

            return $entry->load(['lines.account', 'postedBy', 'organization']);
        });
    }

    protected function generateJournalNumber(): string
    {
        $today = now()->format('Ymd');
        $count = JournalEntry::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return sprintf('JE-%s-%04d', $today, $count);
    }
}
