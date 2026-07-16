<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentIntent;
use App\Services\Integrations\MemberPaymentIntentStateService;
use App\Support\AuditContext;
use Illuminate\Console\Command;

class ExpireMemberOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations {--limit=100 : Maximum intents to process per run}';

    protected $description = 'Release expired store and coffee order reservations safely';

    public function handle(MemberPaymentIntentStateService $stateService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $context = AuditContext::forScheduler();

        // Step 1: Get candidate IDs without locking
        $candidateIds = MemberPaymentIntent::query()
            ->whereIn('payable_type', [
                MemberPaymentIntent::PAYABLE_COFFEE_ORDER,
                MemberPaymentIntent::PAYABLE_STORE_ORDER,
            ])
            ->where('gateway_status', 'PENDING')
            ->whereNull('settled_at')
            ->where(function ($query): void {
                $query->where('reservation_status', MemberPaymentIntent::RESERVATION_RESERVED)
                    ->orWhereNull('reservation_status');
            })
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $metrics = [
            'candidates' => count($candidateIds),
            'expired' => 0,
            'skipped_state_changed' => 0,
            'skipped_not_due' => 0,
            'failed' => 0,
        ];

        // Step 2: Process each ID in its own transaction
        // The state service does its own lockForUpdate + state validation,
        // which is the authoritative path.
        foreach ($candidateIds as $intentId) {
            try {
                $intent = MemberPaymentIntent::query()->find($intentId);
                if ($intent === null) {
                    $metrics['skipped_state_changed']++;

                    continue;
                }

                $ok = $stateService->expireStaleIntent($intent, $context);

                if ($ok) {
                    $metrics['expired']++;
                } else {
                    // State service rejected — could be state changed, paid, or not due
                    $metrics['skipped_state_changed']++;
                }
            } catch (\Throwable) {
                $metrics['failed']++;
            }
        }

        $this->info(sprintf(
            'candidates %d, expired %d, skipped_state_changed %d, skipped_not_due %d, failed %d.',
            $metrics['candidates'],
            $metrics['expired'],
            $metrics['skipped_state_changed'],
            $metrics['skipped_not_due'],
            $metrics['failed'],
        ));

        return self::SUCCESS;
    }
}
