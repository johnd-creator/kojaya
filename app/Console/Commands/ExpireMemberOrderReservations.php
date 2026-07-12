<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentIntent;
use App\Services\Integrations\MemberPaymentIntentStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireMemberOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations {--limit=100 : Maximum intents to process per run}';

    protected $description = 'Release expired store and coffee order reservations safely';

    public function handle(MemberPaymentIntentStateService $stateService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $metrics = ['scanned' => 0, 'expired' => 0, 'skipped_paid' => 0, 'skipped_locked' => 0, 'failed' => 0];

        $query = MemberPaymentIntent::query()
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
            ->limit($limit);

        if (DB::connection()->getDriverName() === 'pgsql') {
            $query->lockForUpdate()->skipLocked();
        }

        $intents = $query->get();
        $metrics['scanned'] = $intents->count();

        foreach ($intents as $intent) {
            try {
                $ok = $stateService->expireStaleIntent($intent);

                if ($ok) {
                    $metrics['expired']++;
                } elseif (strtoupper((string) $intent->gateway_status) === 'PAID') {
                    $metrics['skipped_paid']++;
                } else {
                    $metrics['skipped_locked']++;
                }
            } catch (\Throwable) {
                $metrics['failed']++;
            }
        }

        $this->info(sprintf(
            'Scanned %d, expired %d, skipped_paid %d, skipped_locked %d, failed %d.',
            $metrics['scanned'],
            $metrics['expired'],
            $metrics['skipped_paid'],
            $metrics['skipped_locked'],
            $metrics['failed'],
        ));

        return self::SUCCESS;
    }
}
