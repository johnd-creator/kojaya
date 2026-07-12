<?php

namespace App\Console\Commands;

use App\Models\MemberPaymentIntent;
use App\Services\Cooperative\MemberOrderReservationService;
use Illuminate\Console\Command;

class ExpireMemberOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations {--limit=100 : Maximum intents to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired store and coffee order reservations safely';

    public function handle(MemberOrderReservationService $reservationService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $expired = 0;

        MemberPaymentIntent::query()
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
            ->get()
            ->each(function (MemberPaymentIntent $intent) use ($reservationService, &$expired): void {
                if ($reservationService->expire($intent)) {
                    $expired++;
                }
            });

        $this->info("Expired {$expired} order reservation(s).");

        return self::SUCCESS;
    }
}
