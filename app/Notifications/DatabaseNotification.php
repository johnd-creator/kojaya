<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database-only notification yang ditulis langsung ke tabel notifications
 * oleh App\Services\NotificationService::sendDatabase.
 *
 * Tetap dibuat sebagai class konkret supaya kolom polimorfik `type` pada
 * baris notifications dapat dihidrasi konsisten saat ditampilkan.
 */
class DatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
