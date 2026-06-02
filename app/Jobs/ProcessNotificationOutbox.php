<?php

namespace App\Jobs;

use App\Models\NotificationOutbox;
use App\Services\Integrations\PushNotificationService;
use App\Services\Integrations\WhatsAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessNotificationOutbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $outboxId) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('notification-outbox-'.$this->outboxId))->expireAfter(120)];
    }

    public function handle(
        NotificationService $notificationService,
        PushNotificationService $pushNotificationService,
        WhatsAppNotificationService $whatsAppNotificationService,
    ): void {
        $outbox = $this->claimOutbox();

        if (! $outbox) {
            return;
        }

        try {
            $payload = $outbox->payload;
            $title = (string) ($payload['title'] ?? $outbox->event_type);
            $message = (string) ($payload['message'] ?? '');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            match ($outbox->channel) {
                'database' => $notificationService->sendDatabase($outbox->user, $title, $message, $data),
                'push' => $pushNotificationService->sendOrFail($outbox->user, $title, $message, $data),
                'whatsapp' => $whatsAppNotificationService->sendOrFail($outbox->user, $title, $message, $data),
                default => throw new \InvalidArgumentException("Unsupported notification channel [{$outbox->channel}]."),
            };

            $outbox->forceFill([
                'status' => 'sent',
                'processing_at' => null,
                'sent_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->releaseForRetry($outbox, $exception);
        }
    }

    private function claimOutbox(): ?NotificationOutbox
    {
        return DB::transaction(function (): ?NotificationOutbox {
            $outbox = NotificationOutbox::query()
                ->with('user')
                ->whereKey($this->outboxId)
                ->lockForUpdate()
                ->first();

            if (! $outbox || $outbox->status === 'sent') {
                return null;
            }

            if ($outbox->available_at && $outbox->available_at->isFuture()) {
                return null;
            }

            if ($outbox->status !== 'pending') {
                return null;
            }

            $outbox->forceFill([
                'status' => 'processing',
                'processing_at' => now(),
            ])->save();

            return $outbox;
        });
    }

    private function releaseForRetry(NotificationOutbox $outbox, Throwable $exception): void
    {
        $attempts = $outbox->attempts + 1;
        $exhausted = $attempts >= $outbox->max_attempts;

        $outbox->forceFill([
            'status' => $exhausted ? 'failed' : 'pending',
            'attempts' => $attempts,
            'processing_at' => null,
            'available_at' => $exhausted ? null : now()->addSeconds(min(300, 30 * $attempts)),
            'failed_at' => $exhausted ? now() : null,
            'last_error' => mb_substr($exception->getMessage(), 0, 1000),
        ])->save();

        if (! $exhausted) {
            self::dispatch($outbox->id)->delay($outbox->available_at);
        }
    }
}
