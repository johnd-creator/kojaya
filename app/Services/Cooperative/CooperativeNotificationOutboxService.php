<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeNotificationOutbox;
use App\Models\User;
use Carbon\CarbonImmutable;
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

            if (in_array($locked->status, [
                CooperativeNotificationOutbox::STATUS_DELIVERED,
                CooperativeNotificationOutbox::STATUS_FAILED,
            ], true)) {
                return;
            }

            if ($locked->status === CooperativeNotificationOutbox::STATUS_PENDING) {
                $locked->forceFill([
                    'status' => CooperativeNotificationOutbox::STATUS_PROCESSING,
                    'available_at' => now()->addMinutes(5),
                    'last_error' => null,
                ])->save();
            }

            if ($locked->status !== CooperativeNotificationOutbox::STATUS_PROCESSING) {
                return;
            }

            $payload = $locked->payload ?? [];
            $notificationUuid = $locked->id;

            try {
                $this->dispatchNotification($locked->user_id, $notificationUuid, $payload);

                $locked->forceFill([
                    'status' => CooperativeNotificationOutbox::STATUS_DELIVERED,
                    'delivered_at' => now(),
                ])->save();
            } catch (\Throwable $throwable) {
                $attempts = (int) $locked->attempts + 1;

                if ($attempts >= 5) {
                    $locked->forceFill([
                        'status' => CooperativeNotificationOutbox::STATUS_FAILED,
                        'attempts' => $attempts,
                        'last_error' => class_basename($throwable).': '.substr($throwable->getMessage(), 0, 100),
                    ])->save();
                } else {
                    $locked->forceFill([
                        'status' => CooperativeNotificationOutbox::STATUS_PENDING,
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

        while ($delivered < $limit) {
            $outbox = $this->claimNextPending();

            if ($outbox === null) {
                break;
            }

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
        return DB::table('cooperative_notification_outbox')->insertOrIgnore([
            'id' => $uuid,
            'user_id' => $userId,
            'deduplication_key' => $deduplicationKey,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => CooperativeNotificationOutbox::STATUS_PENDING,
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

        DB::table('notifications')->insertOrIgnore([
            'id' => $uuid,
            'type' => 'App\\Notifications\\CooperativeDatabaseNotification',
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->getKey(),
            'data' => json_encode($payload, JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function claimNextPending(): ?CooperativeNotificationOutbox
    {
        return DB::transaction(function (): ?CooperativeNotificationOutbox {
            $now = CarbonImmutable::now();

            $locked = CooperativeNotificationOutbox::query()
                ->where(function ($query) use ($now): void {
                    $query
                        ->where(function ($pending) use ($now): void {
                            $pending
                                ->where('status', CooperativeNotificationOutbox::STATUS_PENDING)
                                ->where('available_at', '<=', $now);
                        })
                        ->orWhere(function ($processing) use ($now): void {
                            $processing
                                ->where('status', CooperativeNotificationOutbox::STATUS_PROCESSING)
                                ->where('available_at', '<=', $now);
                        });
                })
                ->orderBy('available_at')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->first();

            if ($locked === null) {
                return null;
            }

            $locked->forceFill([
                'status' => CooperativeNotificationOutbox::STATUS_PROCESSING,
                'available_at' => $now->addMinutes(5),
                'last_error' => null,
            ])->save();

            return $locked->refresh();
        });
    }
}
