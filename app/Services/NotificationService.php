<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendEmail(User $user, string $subject, string $content, array $data = []): bool
    {
        try {
            Mail::raw($content, function ($message) use ($user, $subject) {
                $message->to($user->email)
                    ->subject($subject);
            });

            Log::info("Email sent to user {$user->id}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email to user {$user->id}: ".$e->getMessage());

            return false;
        }
    }

    public function sendDatabase(User $user, string $title, string $message, array $data = []): bool
    {
        try {
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\DatabaseNotification',
                'data' => array_merge([
                    'title' => $title,
                    'message' => $message,
                ], $data),
                'read_at' => null,
            ]);

            Log::info("Database notification sent to user {$user->id}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send database notification to user {$user->id}: ".$e->getMessage());

            return false;
        }
    }

    public function send(User $user, array $channels, object $notification): void
    {
        $preference = $user->notificationPreference;

        if ($preference) {
            $enabledChannels = $preference->getEnabledChannels();
            $channels = array_intersect($channels, $enabledChannels);
        }

        foreach ($channels as $channel) {
            try {
                $user->notifyNow($notification);
            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} notification to user {$user->id}: ".$e->getMessage());
            }
        }
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        try {
            $notification = $user->notifications()->where('id', $notificationId)->first();

            if ($notification && ! $notification->read_at) {
                $notification->markAsRead();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: '.$e->getMessage());

            return false;
        }
    }

    public function markAllAsRead(User $user): bool
    {
        try {
            $user->unreadNotifications->markAsRead();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: '.$e->getMessage());

            return false;
        }
    }
}
