<?php

declare(strict_types=1);

namespace App\Services\Cooperative;

use App\Models\ApprovalLog;
use App\Models\CoffeeOrder;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberDocument;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\CooperativeMemberOpeningBalanceLine;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\CooperativeShuAllocation;
use App\Models\CooperativeSupportTicket;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanRestructure;
use App\Models\MemberOnboardingProgress;
use App\Models\MemberPaymentIntent;
use App\Models\MemberResignationRequest;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\PointTransaction;
use App\Models\PosMemberCreditPayment;
use App\Models\PosMemberPoint;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosVoidRequest;
use App\Models\RewardRedemption;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use App\Support\SeedSafety\SeederExecutionProfile;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\CooperativeFixtureReferenceSeeder;
use Database\Seeders\CooperativeMemberLifecycleSeeder;
use Database\Seeders\CooperativePersonaSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use LogicException;

/**
 * Service to orchestrate deterministic cooperative test data reset and reseeding (SEED-07).
 *
 * Scoped strictly to fixture-owned synthetic identities and records.
 * Never performs broad, table-wide, or organization-wide deletes.
 * Preserves operator configurations, reference data, unrelated ERP data, and manual user/member records.
 */
class CooperativeTestDataResetService
{
    /**
     * Allowed environments for baseline reset tooling.
     *
     * @var list<string>
     */
    public const ALLOWED_ENVIRONMENTS = ['local', 'testing', 'playwright'];

    /**
     * Allowed environments for loading invalid edge fixtures (SEED-06).
     *
     * @var list<string>
     */
    public const EDGE_ALLOWED_ENVIRONMENTS = ['testing', 'playwright'];

    /**
     * Preview fixture-owned records without modifying the database.
     *
     * @return array<string, mixed>
     */
    public function dryRun(bool $withEdgeCases = false): array
    {
        $plan = $this->resolveFixturePlan();

        return [
            'is_dry_run' => true,
            'with_edge_cases' => $withEdgeCases,
            'records_to_remove' => $plan['counts'],
            'reseed_plan' => $this->getReseedPlan($withEdgeCases),
        ];
    }

    /**
     * Execute atomic reset and reseed of deterministic cooperative test data.
     *
     * @return array<string, mixed>
     */
    public function reset(bool $withEdgeCases = false): array
    {
        $this->assertEnvironmentSafety($withEdgeCases);

        $plan = $this->resolveFixturePlan();

        DB::transaction(function () use ($plan, $withEdgeCases): void {
            $this->executeCleanup($plan);
            $this->executeReseed($withEdgeCases);
        });

        return [
            'is_dry_run' => false,
            'with_edge_cases' => $withEdgeCases,
            'removed_counts' => $plan['counts'],
            'reseeded' => $this->getReseedPlan($withEdgeCases),
        ];
    }

    /**
     * Validate runtime environment constraints.
     */
    public function assertEnvironmentSafety(bool $withEdgeCases): void
    {
        $env = SeederEnvironmentGuard::assertEnvironmentConsistency();

        if (! SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, $env)) {
            $allowed = implode(', ', SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::LocalTestFixture));
            throw new LogicException("SEED-07 reset tooling is unavailable in this environment [{$env}]. Allowed: {$allowed}.");
        }

        if ($withEdgeCases && ! SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, $env)) {
            throw new LogicException("The --with-edge-cases option is only allowed in testing and playwright environments (current: [{$env}]).");
        }
    }

    /**
     * Resolve exact IDs of all fixture-owned records across models.
     *
     * @return array{
     *     fixtureUserIds: list<int>,
     *     fixtureMemberIds: list<int>,
     *     fixtureSocialAccountIds: list<int>,
     *     fixtureTokenIds: list<int>,
     *     fixtureStoreAccountIds: list<int>,
     *     fixtureStoreLedgerEntryIds: list<int>,
     *     fixtureLoanIds: list<int>,
     *     fixtureInvoiceIds: list<int>,
     *     fixturePaymentIds: list<int>,
     *     fixtureReceiptIds: list<int>,
     *     fixtureLedgerEntryIds: list<int>,
     *     fixturePosTxIds: list<int>,
     *     fixturePosProductIds: list<int>,
     *     counts: array<string, int>
     * }
     */
    public function resolveFixturePlan(): array
    {
        // 1. Resolve Fixture Users (seed.*@kojaya.test and legacy demo cooperative users)
        $fixtureUserIds = User::query()
            ->where(function ($query): void {
                $query->where('email', 'like', 'seed.%@kojaya.test')
                    ->orWhereIn('email', [
                        'admin.kop@koj.id',
                        'kasir@koperasijayabersama.id',
                        'admin.kop@koperasijayabersama.id',
                    ]);
            })
            // Safety exclusion: NEVER touch UI audit or ERP users
            ->where('email', 'not like', 'ui.%@kojaya.test')
            ->where('email', '!=', 'admin@erp.com')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 2. Resolve Fixture Members (DEV-KOP-*, DEMO-KOP-*, DEMO-ANG-*, seed emails, or bound to fixture users)
        $fixtureMemberIds = CooperativeMember::withTrashed()
            ->where(function ($query) use ($fixtureUserIds): void {
                $query->where('member_no', 'like', 'DEV-KOP-%')
                    ->orWhere('no_anggota', 'like', 'DEV-KOP-%')
                    ->orWhere('member_no', 'like', 'DEMO-KOP-%')
                    ->orWhere('no_anggota', 'like', 'DEMO-KOP-%')
                    ->orWhere('member_no', 'like', 'DEMO-ANG-%')
                    ->orWhere('no_anggota', 'like', 'DEMO-ANG-%')
                    ->orWhere('email', 'like', 'seed.member.%@kojaya.test')
                    ->orWhere('email', 'like', 'demo.anggota%@koperasijayabersama.id');

                if (! empty($fixtureUserIds)) {
                    $query->orWhereIn('user_id', $fixtureUserIds);
                }
            })
            // Safety exclusion: NEVER touch UI audit or manual QA members
            ->where('member_no', 'not like', 'AUD-%')
            ->where('no_anggota', 'not like', 'AUD-%')
            ->where('member_no', 'not like', 'MANUAL-%')
            ->where('no_anggota', 'not like', 'MANUAL-%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 3. Resolve Fixture Social Accounts (P12 Google SSO and any bound to fixture users)
        $fixtureSocialAccountIds = SocialAccount::query()
            ->where(function ($query) use ($fixtureUserIds): void {
                $query->where('provider_id', 'like', 'google-seed-%');
                if (! empty($fixtureUserIds)) {
                    $query->orWhereIn('user_id', $fixtureUserIds);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 4. Resolve Personal Access Tokens for Fixture Users
        $fixtureTokenIds = ! empty($fixtureUserIds)
            ? PersonalAccessToken::query()
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $fixtureUserIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        // 5. Resolve Store Accounts & Ledgers
        $fixtureStoreAccountIds = ! empty($fixtureMemberIds)
            ? MemberStoreAccount::query()
                ->whereIn('cooperative_member_id', $fixtureMemberIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $fixtureStoreLedgerEntryIds = MemberStoreLedgerEntry::query()
            ->where(function ($query) use ($fixtureStoreAccountIds): void {
                if (! empty($fixtureStoreAccountIds)) {
                    $query->whereIn('account_id', $fixtureStoreAccountIds);
                }
                $query->orWhere('idempotency_key', 'like', 'seed-store-ledger:%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 6. Resolve Loans
        $fixtureLoanIds = Loan::query()
            ->where(function ($query) use ($fixtureMemberIds): void {
                if (! empty($fixtureMemberIds)) {
                    $query->whereIn('cooperative_member_id', $fixtureMemberIds);
                }
                $query->orWhere('reference_no', 'like', 'SEED-LOAN-%');
            })
            ->where('reference_no', 'not like', 'AUD-%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 7. Resolve Invoices
        $fixtureInvoiceIds = ! empty($fixtureMemberIds)
            ? CooperativeDuesInvoice::query()
                ->whereIn('cooperative_member_id', $fixtureMemberIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        // 8. Resolve Payments
        $fixturePaymentIds = CooperativePayment::query()
            ->where(function ($query) use ($fixtureMemberIds, $fixtureInvoiceIds): void {
                if (! empty($fixtureMemberIds)) {
                    $query->whereIn('cooperative_member_id', $fixtureMemberIds);
                }
                if (! empty($fixtureInvoiceIds)) {
                    $query->orWhereIn('cooperative_dues_invoice_id', $fixtureInvoiceIds);
                }
                $query->orWhere('reference_no', 'like', 'SEED-PAY-%')
                    ->orWhere('reference_no', 'like', 'DEMO-%')
                    ->orWhere('reference_no', 'like', 'ANGGOTA-POKOK-%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 9. Resolve Receipts
        $fixtureReceiptIds = CooperativeReceipt::query()
            ->where(function ($query) use ($fixtureMemberIds, $fixturePaymentIds): void {
                if (! empty($fixtureMemberIds)) {
                    $query->whereIn('cooperative_member_id', $fixtureMemberIds);
                }
                if (! empty($fixturePaymentIds)) {
                    $query->orWhereIn('cooperative_payment_id', $fixturePaymentIds);
                }
                $query->orWhere('receipt_no', 'like', 'SEED-RC-%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 10. Resolve Cooperative Ledgers
        $fixtureLedgerEntryIds = CooperativeLedgerEntry::query()
            ->where(function ($query) use ($fixtureMemberIds, $fixturePaymentIds, $fixtureLoanIds): void {
                if (! empty($fixtureMemberIds)) {
                    $query->whereIn('cooperative_member_id', $fixtureMemberIds);
                }
                if (! empty($fixturePaymentIds)) {
                    $query->orWhereIn('cooperative_payment_id', $fixturePaymentIds);
                }
                if (! empty($fixtureLoanIds)) {
                    $query->orWhere(function ($q) use ($fixtureLoanIds): void {
                        $q->where('source_type', Loan::class)->whereIn('source_id', $fixtureLoanIds);
                    });
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 11. Resolve POS Transactions
        $fixturePosTxIds = PosTransaction::query()
            ->where(function ($query) use ($fixtureMemberIds): void {
                if (! empty($fixtureMemberIds)) {
                    $query->whereIn('cooperative_member_id', $fixtureMemberIds);
                }
                $query->orWhere('transaction_no', 'like', 'SEED-POS-TX-%')
                    ->orWhere('client_reference', 'like', 'SEED-POS-REF-%');
            })
            ->where('transaction_no', 'not like', 'AUD-%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 12. Resolve POS Products (strictly canonical seeder products)
        $fixturePosProductIds = PosProduct::query()
            ->whereIn('barcode', ['8999001000010', '8999001000027', '8999001000034', '8999001000041'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'fixtureUserIds' => $fixtureUserIds,
            'fixtureMemberIds' => $fixtureMemberIds,
            'fixtureSocialAccountIds' => $fixtureSocialAccountIds,
            'fixtureTokenIds' => $fixtureTokenIds,
            'fixtureStoreAccountIds' => $fixtureStoreAccountIds,
            'fixtureStoreLedgerEntryIds' => $fixtureStoreLedgerEntryIds,
            'fixtureLoanIds' => $fixtureLoanIds,
            'fixtureInvoiceIds' => $fixtureInvoiceIds,
            'fixturePaymentIds' => $fixturePaymentIds,
            'fixtureReceiptIds' => $fixtureReceiptIds,
            'fixtureLedgerEntryIds' => $fixtureLedgerEntryIds,
            'fixturePosTxIds' => $fixturePosTxIds,
            'fixturePosProductIds' => $fixturePosProductIds,
            'counts' => [
                'users' => count($fixtureUserIds),
                'members' => count($fixtureMemberIds),
                'social_accounts' => count($fixtureSocialAccountIds),
                'personal_access_tokens' => count($fixtureTokenIds),
                'store_accounts' => count($fixtureStoreAccountIds),
                'store_ledger_entries' => count($fixtureStoreLedgerEntryIds),
                'loans' => count($fixtureLoanIds),
                'dues_invoices' => count($fixtureInvoiceIds),
                'payments' => count($fixturePaymentIds),
                'receipts' => count($fixtureReceiptIds),
                'cooperative_ledger_entries' => count($fixtureLedgerEntryIds),
                'pos_transactions' => count($fixturePosTxIds),
                'pos_products' => count($fixturePosProductIds),
            ],
        ];
    }

    /**
     * Execute targeted deletion in strict FK-safe dependency order.
     *
     * @param  array<string, mixed>  $plan
     */
    protected function executeCleanup(array $plan): void
    {
        $memberIds = $plan['fixtureMemberIds'];
        $userIds = $plan['fixtureUserIds'];
        $loanIds = $plan['fixtureLoanIds'];
        $storeAccountIds = $plan['fixtureStoreAccountIds'];
        $posTxIds = $plan['fixturePosTxIds'];
        $paymentIds = $plan['fixturePaymentIds'];
        $invoiceIds = $plan['fixtureInvoiceIds'];
        $productIds = $plan['fixturePosProductIds'];

        // 1. Personal Access Tokens for fixture users
        if (! empty($userIds)) {
            PersonalAccessToken::query()
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();
        }

        // 2. Social Accounts for fixture users & google-seed-*
        if (! empty($plan['fixtureSocialAccountIds'])) {
            SocialAccount::query()->whereIn('id', $plan['fixtureSocialAccountIds'])->delete();
        }

        // 3. POS Sub-records & Transactions
        if (! empty($posTxIds)) {
            CoffeeOrder::query()->whereIn('pos_transaction_id', $posTxIds)->delete();
            PosTransactionItem::query()->whereIn('pos_transaction_id', $posTxIds)->delete();
            PosPayment::query()->whereIn('pos_transaction_id', $posTxIds)->delete();
            PosReturn::query()->whereIn('pos_transaction_id', $posTxIds)->delete();
            PosVoidRequest::query()->whereIn('pos_transaction_id', $posTxIds)->delete();
            PosTransaction::query()->whereIn('id', $posTxIds)->delete();
        }

        // 4. Member-level POS & Points
        if (! empty($memberIds)) {
            CoffeeOrder::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            PosMemberCreditPayment::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            PosMemberPoint::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            PointTransaction::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            RewardRedemption::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            MemberPaymentIntent::query()->whereIn('cooperative_member_id', $memberIds)->delete();
        }

        // 5. Store Accounts & Ledgers
        if (! empty($plan['fixtureStoreLedgerEntryIds'])) {
            MemberStoreLedgerEntry::query()->whereIn('id', $plan['fixtureStoreLedgerEntryIds'])->delete();
        }
        if (! empty($storeAccountIds)) {
            MemberStoreFundingRequest::query()->whereIn('account_id', $storeAccountIds)->delete();
            MemberStoreDelegate::query()->whereIn('account_id', $storeAccountIds)->delete();
            MemberStoreAccount::query()->whereIn('id', $storeAccountIds)->delete();
        }

        // 6. Loans & Children
        if (! empty($loanIds)) {
            ApprovalLog::query()
                ->where('subject_type', Loan::class)
                ->whereIn('subject_id', array_map('strval', $loanIds))
                ->delete();
            LoanPayment::query()->whereIn('loan_id', $loanIds)->delete();
            LoanInstallment::query()->whereIn('loan_id', $loanIds)->delete();
            LoanRestructure::query()->whereIn('loan_id', $loanIds)->delete();
            Loan::query()->whereIn('id', $loanIds)->delete();
        }

        // 7. Receipts, Cooperative Ledgers, Payments, Invoices
        if (! empty($plan['fixtureReceiptIds'])) {
            CooperativeReceipt::query()->whereIn('id', $plan['fixtureReceiptIds'])->delete();
        }
        if (! empty($plan['fixtureLedgerEntryIds'])) {
            CooperativeLedgerEntry::query()->whereIn('id', $plan['fixtureLedgerEntryIds'])->delete();
        }
        if (! empty($paymentIds)) {
            CooperativePayment::query()->whereIn('id', $paymentIds)->delete();
        }
        if (! empty($invoiceIds)) {
            CooperativeDuesInvoice::query()->whereIn('id', $invoiceIds)->delete();
        }

        // 8. Member Supporting Records
        if (! empty($memberIds)) {
            CooperativeShuAllocation::query()->whereIn('cooperative_member_id', $memberIds)->delete();

            $batchIds = CooperativeMemberOpeningBalanceBatch::query()
                ->whereIn('cooperative_member_id', $memberIds)
                ->pluck('id');
            if ($batchIds->isNotEmpty()) {
                CooperativeMemberOpeningBalanceLine::query()->whereIn('batch_id', $batchIds)->delete();
                CooperativeMemberOpeningBalanceBatch::query()->whereIn('id', $batchIds)->delete();
            }

            MemberOnboardingProgress::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            CooperativeMemberDocument::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            MemberResignationRequest::query()->whereIn('cooperative_member_id', $memberIds)->delete();
            CooperativeSupportTicket::query()->whereIn('cooperative_member_id', $memberIds)->delete();

            // 9. Cooperative Members
            CooperativeMember::withTrashed()->whereIn('id', $memberIds)->forceDelete();
        }

        // 10. POS Products (strictly canonical seeder products)
        if (! empty($productIds)) {
            PosProduct::query()->whereIn('id', $productIds)->delete();
        }

        // 11. Fixture Users
        if (! empty($userIds)) {
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $userIds)->delete();
            DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $userIds)->delete();
            User::query()->whereIn('id', $userIds)->forceDelete();
        }
    }

    /**
     * Reseed canonical Phase-3 dataset.
     */
    protected function executeReseed(bool $withEdgeCases): void
    {
        (new CooperativeFixtureReferenceSeeder)->run();
        (new CooperativePersonaSeeder)->run();
        (new CooperativeMemberLifecycleSeeder)->run();
        (new CooperativeFinancialFixtureSeeder)->run();

        if ($withEdgeCases) {
            (new CooperativeEdgeCaseFixtureSeeder)->run();
        }
    }

    /**
     * @return list<string>
     */
    private function getReseedPlan(bool $withEdgeCases): array
    {
        $plan = [
            'CooperativeFixtureReferenceSeeder',
            'CooperativePersonaSeeder',
            'CooperativeMemberLifecycleSeeder',
            'CooperativeFinancialFixtureSeeder',
        ];

        if ($withEdgeCases) {
            $plan[] = 'CooperativeEdgeCaseFixtureSeeder';
        }

        return $plan;
    }
}
