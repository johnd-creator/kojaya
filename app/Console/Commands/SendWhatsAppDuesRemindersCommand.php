<?php

namespace App\Console\Commands;

use App\Services\WhatsAppReminderService;
use Illuminate\Console\Command;

class SendWhatsAppDuesRemindersCommand extends Command
{
    protected $signature = 'notifications:whatsapp-dues-reminders {--days=3 : Include unpaid invoices due within this many days}';

    protected $description = 'Queue WhatsApp reminders for unpaid cooperative dues invoices';

    public function handle(WhatsAppReminderService $reminderService): int
    {
        $queued = $reminderService->enqueueDueReminders((int) $this->option('days'));

        $this->info("Queued {$queued} WhatsApp dues reminder notifications.");

        return self::SUCCESS;
    }
}
