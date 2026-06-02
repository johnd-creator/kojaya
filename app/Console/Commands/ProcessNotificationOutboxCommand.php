<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotificationOutbox;
use App\Models\NotificationOutbox;
use Illuminate\Console\Command;

class ProcessNotificationOutboxCommand extends Command
{
    protected $signature = 'notifications:outbox:process {--limit=100 : Maximum outbox rows to dispatch}';

    protected $description = 'Dispatch pending notification outbox rows for delivery and retry';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $outboxes = NotificationOutbox::query()
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->orderBy('available_at')
            ->limit($limit)
            ->get();

        foreach ($outboxes as $outbox) {
            ProcessNotificationOutbox::dispatch($outbox->id);
        }

        $this->info("Dispatched {$outboxes->count()} notification outbox jobs.");

        return self::SUCCESS;
    }
}
