<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentIntent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecoverStaleChargeCreating extends Command
{
    protected $signature = 'orders:recover-stale-charges {--minutes=5 : staleness threshold in minutes} {--limit=50 : max intents per run}';

    protected $description = 'Recover member payment intents stuck in CHARGE_CREATING';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, min((int) $this->option('limit'), 500));
        $threshold = now()->subMinutes($minutes);

        $recovered = 0;

        MemberPaymentIntent::query()
            ->where('gateway_status', 'CHARGE_CREATING')
            ->where('updated_at', '<=', $threshold)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (MemberPaymentIntent $intent) use (&$recovered): void {
                $ok = DB::transaction(function () use ($intent): bool {
                    $locked = MemberPaymentIntent::query()
                        ->lockForUpdate()
                        ->findOrFail($intent->id);

                    if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                        return false;
                    }

                    $locked->forceFill(['gateway_status' => 'PENDING'])->save();

                    return true;
                });

                if ($ok) {
                    $recovered++;
                }
            });

        $this->info("Recovered {$recovered} stale CHARGE_CREATING intent(s).");

        return self::SUCCESS;
    }
}
