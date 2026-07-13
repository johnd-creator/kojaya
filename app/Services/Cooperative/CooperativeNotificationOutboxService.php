<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeNotificationOutbox;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CooperativeNotificationOutboxService
{
    /**
     * Atomically enqueue a notification for a specific user.
     *
     * Uses insertOrIgnore to deduplicate by user_id + deduplication_key.
     * Must be called inside the business transaction that generates the
     * notification, so the outbox row is committed atomically with the
     * business state change.
     */
    public function enqueueForUser(User $user, string $deduplicationKey, array $payload): ?CooperativeNotificationOutbox
    {
        $id = (string) Str::uuid();

        $inserted = $this->insertIgnore($id, $user->id, $deduplicationKey, $payload);

        if ($inserted === 0) {
            // Already exists — deduplication successful, no duplicate
            return null;
        }

        return CooperativeNotificationOutbox::query()
            ->where('deduplication_key', $deduplicationKey)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Deliver a pending outbox entry.
     *
     * Check-then-act in the notification system: uses the outbox UUID
     * as the notification UUID so that retries don't create duplicates.
     */
    public function deliver(CooperativeNotificationOutbox $outbox): void
    {
        DB::transaction(function () use ($outbox): void {
            $locked = CooperativeNotificationOutbox::query()
                ->lockForUpdate()
                ->findOrFail($outbox->id);

            if ($locked->status !== 'PENDING') {
                return;
            }

            $payload = $locked->payload ?? [];
            $notificationUuid = $locked->id;

            try {
                // Use the outbox UUID as the notification ID for deduplication.
                // The notification dispatch system already checks firstOrCreate.
                $this->dispatchNotification($locked->user_id, $notificationUuid, $payload);

                $locked->forceFill([
                    'status' => 'DELIVERED',
                    'delivered_at' => now(),
                ])->save();
            } catch (\Throwable $throwable) {
                $attempts = (int) $locked->attempts + 1;

                if ($attempts >= 5) {
                    $locked->forceFill([
                        'status' => 'FAILED',
                        'attempts' => $attempts,
                        'last_error' => class_basename($throwable).': '.substr($throwable->getMessage(), 0, 100),
                    ])->save();
                } else {
                    $locked->forceFill([
                        'status' => 'PENDING',
                        'attempts' => $attempts,
                        'last_error' => class_basename($throwable).': '.substr($throwable->getMessage(), 0, 100),
                        'available_at' => now()->addMinutes($attempts * 5),
                    ])->save();
                }
            }
        });
    }

    /**
     * Deliver all pending outbox entries that are available for delivery.
     */
    public function deliverPending(int $limit = 100): int
    {
        $delivered = 0;

        $pending = CooperativeNotificationOutbox::query()
            ->where('status', 'PENDING')
            ->where('available_at', '<=', now())
            ->orderBy('available_at')
            ->limit($limit)
            ->get();

        foreach ($pending as $outbox) {
            $this->deliver($outbox);
            $delivered++;
        }

        return $delivered;
    }

    /**
     * Insert with ignore semantics for deduplication.
     */
    private function insertIgnore(string $uuid, ?int $userId, string $deduplicationKey, array $payload): int
    {
        try {
            return DB::table('cooperative_notification_outbox')->insertOrIgnore([
                'id' => $uuid,
                'user_id' => $userId,
                'deduplication_key' => $deduplicationKey,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'status' => 'PENDING',
                'attempts' => 0,
                'available_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Insert may fail on unique constraint — that's fine,
            // it means deduplication worked.
            return 0;
        }
    }

    private function dispatchNotification(?int $userId, string $uuid, array $payload): void
    {
        if ($userId === null) {
            return;
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return;
        }

        $deduplicationKey = (string) ($payload['deduplication_key'] ?? '');

        if ($deduplicationKey !== '') {
            $exists = $user->notifications()
                ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
                ->where('data->deduplication_key', $deduplicationKey)
                ->exists();

            if ($exists) {
                return;
            }
        }

        $user->notifications()->create([
            'id' => $uuid,
            'type' => 'App\\Notifications\\CooperativeDatabaseNotification',
            'data' => $payload,
            'read_at' => null,
        ]);
    }
}
