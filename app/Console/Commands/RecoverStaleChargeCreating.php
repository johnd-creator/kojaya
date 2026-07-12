<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverStaleChargeCreating extends Command
{
    protected $signature = 'orders:recover-stale-charges {--minutes=5 : staleness threshold in minutes} {--limit=50 : max intents per run}';

    protected $description = 'Recover member payment intents stuck in CHARGE_CREATING (marks attempt UNKNOWN, does NOT start new attempt)';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, min((int) $this->option('limit'), 500));
        $threshold = now()->subMinutes($minutes);

        $candidates = MemberPaymentIntent::query()
            ->where('gateway_status', 'CHARGE_CREATING')
            ->where('updated_at', '<=', $threshold)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $recovered = 0;
        $unknown = 0;

        foreach ($candidates as $intentId) {
            $result = $this->recoverIntent($intentId);
            if ($result === 'reset') {
                $recovered++;
            } elseif ($result === 'unknown') {
                $unknown++;
            }
        }

        $this->info("Reset {$recovered} stale CHARGE_CREATING intent(s), {$unknown} marked UNKNOWN.");

        return self::SUCCESS;
    }

    private function recoverIntent(int $intentId): string
    {
        return DB::transaction(function () use ($intentId): string {
            $locked = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->findOrFail($intentId);

            if (strtoupper((string) $locked->gateway_status) !== 'CHARGE_CREATING') {
                return 'skip';
            }

            $attempt = (int) $locked->charge_attempt;

            // Check if attempt record has a confirmed provider reference
            $attemptRecord = MemberPaymentChargeAttempt::query()
                ->where('member_payment_intent_id', $locked->id)
                ->where('attempt', $attempt)
                ->lockForUpdate()
                ->first();

            if ($attemptRecord && $attemptRecord->provider_reference) {
                // Provider charge exists: persist it instead of creating a new one
                Log::info('Recovery found confirmed provider reference for stale attempt', [
                    'intent_id' => $locked->id,
                    'attempt' => $attempt,
                    'provider_reference' => $attemptRecord->provider_reference,
                ]);

                $locked->forceFill([
                    'gateway_reference' => $attemptRecord->provider_reference,
                    'gateway_status' => 'PENDING',
                ])->save();

                return 'reset';
            }

            // Mark attempt as UNKNOWN and reset intent to PENDING.
            // Do NOT start a new attempt — the next ensureCharge call will
            // detect CHARGE_CREATING -> PENDING transition and proceed safely.
            if ($attemptRecord) {
                $attemptRecord->forceFill([
                    'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
                    'completed_at' => now(),
                ])->save();
            }

            $locked->forceFill(['gateway_status' => 'PENDING'])->save();

            Log::warning('Recovery: stale CHARGE_CREATING marked UNKNOWN, intent reset to PENDING', [
                'intent_id' => $locked->id,
                'attempt' => $attempt,
            ]);

            return $attemptRecord ? 'unknown' : 'reset';
        });
    }
}
