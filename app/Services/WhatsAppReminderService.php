<?php

namespace App\Services;

use App\Models\CooperativeDuesInvoice;
use App\Models\Leave;

class WhatsAppReminderService
{
    public function __construct(private readonly NotificationOutboxService $outboxService) {}

    public function enqueueDuesReminder(CooperativeDuesInvoice $invoice): bool
    {
        $invoice->loadMissing(['member.user.notificationPreference', 'contributionType']);
        $user = $invoice->member?->user;

        if (! $user) {
            return false;
        }

        $outbox = $this->outboxService->enqueueWhatsApp($user, 'cooperative.dues.reminder', [
            'title' => 'Pengingat iuran Kojayaku',
            'message' => sprintf(
                'Tagihan %s periode %s sebesar Rp%s jatuh tempo pada %s.',
                $invoice->contributionType?->name ?? 'iuran',
                $invoice->period,
                number_format((float) $invoice->amount, 0, ',', '.'),
                $invoice->due_date?->format('d/m/Y') ?? '-',
            ),
            'data' => [
                'invoice_id' => $invoice->id,
                'period' => $invoice->period,
                'amount' => (float) $invoice->amount,
                'due_date' => $invoice->due_date?->toDateString(),
                'reference' => 'DUES-'.$invoice->id,
            ],
        ]);

        return $outbox !== null;
    }

    public function enqueueDueReminders(int $daysAhead = 3): int
    {
        $dueUntil = today()->addDays(max(0, $daysAhead));
        $queued = 0;

        CooperativeDuesInvoice::query()
            ->with(['member.user.notificationPreference', 'contributionType'])
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->whereDate('due_date', '<=', $dueUntil)
            ->orderBy('due_date')
            ->chunkById(100, function ($invoices) use (&$queued): void {
                foreach ($invoices as $invoice) {
                    if ($this->enqueueDuesReminder($invoice)) {
                        $queued++;
                    }
                }
            });

        return $queued;
    }

    public function enqueueLeaveStatus(Leave $leave): bool
    {
        $leave->loadMissing(['employee.user.notificationPreference', 'type']);
        $user = $leave->employee?->user;

        if (! $user) {
            return false;
        }

        $status = strtolower((string) $leave->status);
        $outbox = $this->outboxService->enqueueWhatsApp($user, 'ess.leave.status', [
            'title' => 'Status cuti diperbarui',
            'message' => sprintf(
                'Pengajuan cuti %s tanggal %s sampai %s telah %s.',
                $leave->type?->name ?? 'Anda',
                $leave->start_date?->format('d/m/Y') ?? '-',
                $leave->end_date?->format('d/m/Y') ?? '-',
                $status,
            ),
            'data' => [
                'leave_id' => $leave->id,
                'status' => $leave->status,
                'reference' => 'LEAVE-'.$leave->id,
            ],
        ]);

        return $outbox !== null;
    }
}
