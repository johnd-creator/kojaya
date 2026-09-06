<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\PosDailyClosing;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosDailyClosingService
{
    /**
     * Acquire a database row-level lock for update on (organization_id, closing_date).
     * Atomically creates an open placeholder row if none exists yet.
     */
    public function acquireLockRow(string $organizationId, string $date): PosDailyClosing
    {
        if (empty($organizationId)) {
            throw new \InvalidArgumentException('Organization ID is required to acquire closing lock.');
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $closing = PosDailyClosing::query()
                ->where('organization_id', $organizationId)
                ->whereDate('closing_date', $date)
                ->lockForUpdate()
                ->first();

            if ($closing !== null) {
                return $closing;
            }

            DB::table('pos_daily_closings')->insertOrIgnore([
                'organization_id' => $organizationId,
                'closing_date' => $date,
                'is_locked' => false,
                'closed_at' => null,
                'closed_by' => null,
                'transaction_count' => 0,
                'gross_sales' => 0,
                'total_discount' => 0,
                'total_void' => 0,
                'total_return' => 0,
                'net_sales' => 0,
                'member_credit_outstanding' => 0,
                'payment_summary' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $closing = PosDailyClosing::query()
                ->where('organization_id', $organizationId)
                ->whereDate('closing_date', $date)
                ->lockForUpdate()
                ->first();

            if ($closing !== null) {
                return $closing;
            }
        }

        throw new \RuntimeException("Failed to acquire POS daily closing lock for organization {$organizationId} on {$date}.");
    }

    public function closeDay(string $date, User $user, ?string $targetOrgId = null): PosDailyClosing
    {
        if (! $user->can('view_pos_reports')) {
            throw new AuthorizationException('Pengguna tidak memiliki izin view_pos_reports untuk closing POS.');
        }

        $organizationId = $this->resolveClosingOrganization($user, $targetOrgId);

        $existing = $this->findByDate($date, $organizationId);
        if ($existing && $existing->is_locked) {
            throw ValidationException::withMessages([
                'date' => 'Hari ini sudah ditutup dan terkunci.',
            ]);
        }

        return DB::transaction(function () use ($date, $user, $organizationId): PosDailyClosing {
            $closing = $this->acquireLockRow($organizationId, $date);

            if ($closing->is_locked) {
                throw ValidationException::withMessages([
                    'date' => 'Hari ini sudah ditutup dan terkunci.',
                ]);
            }

            $summary = $this->summaryForDate($date, $organizationId);
            $paymentSummary = $this->paymentSummaryForDate($date, $organizationId);
            $memberCreditOutstanding = $this->memberCreditOutstanding($organizationId);

            $closing->forceFill([
                'organization_id' => $organizationId,
                'closing_date' => $date,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'transaction_count' => $summary['transaction_count'],
                'gross_sales' => $summary['gross_sales'],
                'total_discount' => $summary['total_discount'],
                'total_void' => $summary['total_void'],
                'total_return' => $summary['total_return'],
                'net_sales' => $summary['net_sales'],
                'member_credit_outstanding' => $memberCreditOutstanding,
                'payment_summary' => $paymentSummary,
                'is_locked' => true,
            ])->save();

            $this->postClosingJournal($closing, $user);

            return $closing->refresh();
        });
    }

    public function resolveClosingOrganization(User $user, ?string $targetOrgId = null): string
    {
        $scopeService = app(\App\Services\Authorization\OrganizationScopeService::class);
        $visibility = $scopeService->visibilityFor($user, 'view_cooperative_all');

        if ($visibility->state === \App\Enums\OrganizationVisibilityState::DENIED) {
            throw new AuthorizationException('Pengguna tanpa organisasi tidak diizinkan melakukan closing.');
        }

        if (! $visibility->global) {
            if ($targetOrgId !== null && (string) $targetOrgId !== (string) $visibility->organizationId) {
                throw new AuthorizationException('Pengguna tidak diizinkan mengakses organisasi lain.');
            }

            return (string) $visibility->organizationId;
        }

        $resolved = $targetOrgId ?? session('active_organization_id');
        if (empty($resolved)) {
            throw ValidationException::withMessages([
                'organization_id' => 'Target organisasi wajib ditentukan untuk pengguna global.',
            ]);
        }

        try {
            return $scopeService->assertOrganizationIdentifier($resolved);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'organization_id' => 'Organisasi target tidak ditemukan.',
            ]);
        }
    }

    /**
     * @return array<string, float|int>
     */
    public function summaryForDate(string $date, string $organizationId): array
    {
        if (empty($organizationId)) {
            throw new \InvalidArgumentException('Organization context is required for POS daily closing summary.');
        }

        $base = PosTransaction::query()
            ->where('organization_id', $organizationId)
            ->whereDate('sold_at', $date);

        $completed = (clone $base)->where('status', 'COMPLETED');
        $voided = (clone $base)->where('status', 'VOIDED');
        $returns = PosReturn::query()
            ->whereDate('returned_at', $date)
            ->whereHas('transaction', fn ($q) => $q->where('organization_id', $organizationId));

        $grossSales = (float) (clone $completed)->sum('total_amount');
        $totalDiscount = (float) (clone $completed)->sum('discount_amount');
        $totalVoid = (float) (clone $voided)->sum('total_amount');
        $totalReturn = (float) (clone $returns)->sum('total_amount');
        $netSales = round($grossSales - $totalReturn, 2);

        return [
            'transaction_count' => (clone $completed)->count(),
            'gross_sales' => $grossSales,
            'total_discount' => $totalDiscount,
            'total_void' => $totalVoid,
            'total_return' => $totalReturn,
            'net_sales' => $netSales,
        ];
    }

    /**
     * @return array<int, array<string, float|int|string>>
     */
    public function paymentSummaryForDate(string $date, string $organizationId): array
    {
        if (empty($organizationId)) {
            throw new \InvalidArgumentException('Organization context is required for POS payment summary.');
        }

        return PosTransaction::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', $date)
            ->with('payments')
            ->get()
            ->flatMap(fn ($trx) => $trx->payments)
            ->groupBy('payment_method')
            ->map(fn ($group, $method): array => [
                'method' => $method,
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ])
            ->values()
            ->all();
    }

    public function memberCreditOutstanding(string $organizationId): float
    {
        if (empty($organizationId)) {
            throw new \InvalidArgumentException('Organization context is required for member credit outstanding.');
        }

        return (float) DB::table('cooperative_members')
            ->where('organization_id', $organizationId)
            ->whereNotNull('organization_id')
            ->sum('outstanding_balance');
    }

    public function isLocked(string $date, string $organizationId): bool
    {
        if (empty($organizationId)) {
            throw new \InvalidArgumentException('Organization context is required for POS closing lock check.');
        }

        $closing = $this->findByDate($date, $organizationId);

        return (bool) ($closing?->is_locked);
    }

    private function findByDate(string $date, string $organizationId): ?PosDailyClosing
    {
        return PosDailyClosing::query()
            ->where('organization_id', $organizationId)
            ->whereDate('closing_date', $date)
            ->first();
    }

    private function postClosingJournal(PosDailyClosing $closing, User $user): void
    {
        $revenue = (float) $closing->net_sales;
        if ($revenue <= 0) {
            return;
        }

        $userMemberId = DB::table('cooperative_members')
            ->where('user_id', $user->id)
            ->where('organization_id', $closing->organization_id)
            ->value('id');

        CooperativeLedgerEntry::query()->firstOrCreate(
            [
                'source_type' => PosDailyClosing::class,
                'source_id' => $closing->id,
                'entry_type' => 'POS_DAILY_CLOSING',
            ],
            [
                'cooperative_member_id' => $userMemberId ?: null,
                'organization_id' => $closing->organization_id,
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $revenue,
                'description' => "Penjualan POS netto tgl {$closing->closing_date->toDateString()}",
                'posted_at' => $closing->closing_date->toDateString(),
            ]
        );
    }
}
