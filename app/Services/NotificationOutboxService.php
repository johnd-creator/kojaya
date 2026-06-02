<?php

namespace App\Services;

use App\Jobs\ProcessNotificationOutbox;
use App\Models\NotificationOutbox;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class NotificationOutboxService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        User $user,
        string $eventType,
        string $channel,
        array $payload,
        ?CarbonInterface $availableAt = null,
        int $maxAttempts = 5,
    ): NotificationOutbox {
        $outbox = NotificationOutbox::query()->create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'channel' => $channel,
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => $availableAt ?? now(),
        ]);

        DB::afterCommit(fn (): mixed => ProcessNotificationOutbox::dispatch($outbox->id));

        return $outbox;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueueDatabase(User $user, string $eventType, array $payload): NotificationOutbox
    {
        return $this->enqueue($user, $eventType, 'database', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueuePush(User $user, string $eventType, array $payload): NotificationOutbox
    {
        return $this->enqueue($user, $eventType, 'push', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueueWhatsApp(User $user, string $eventType, array $payload): ?NotificationOutbox
    {
        $preference = $user->notificationPreference;

        if (! $preference?->isChannelEnabled('whatsapp')) {
            return null;
        }

        if (! filled($preference->whatsapp_phone) && ! filled($user->cooperativeMember?->phone)) {
            return null;
        }

        return $this->enqueue($user, $eventType, 'whatsapp', $payload);
    }
}
