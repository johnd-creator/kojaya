<?php

namespace App\Console\Commands;

use App\Services\Cooperative\CooperativeNotificationOutboxService;
use Illuminate\Console\Command;

class DeliverCooperativeNotificationOutbox extends Command
{
    protected $signature = 'cooperative:deliver-notification-outbox {--limit=100 : Maximum rows to claim and deliver}';

    protected $description = 'Deliver cooperative notification outbox rows with exact-once claiming';

    public function handle(CooperativeNotificationOutboxService $outboxService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));
        $delivered = $outboxService->deliverPending($limit);

        $this->info("Delivered {$delivered} cooperative notification outbox entr".($delivered === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
