<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : (json_decode((string) $this->data, true) ?? []);
        $subject = $data['subject'] ?? [
            'type' => $data['subject_type'] ?? null,
            'id' => $data['subject_id'] ?? null,
            'label' => $data['subject_label'] ?? null,
        ];
        $action = $data['action'] ?? [
            'label' => $data['action_label'] ?? 'Buka detail',
            'url' => $data['action_url'] ?? $data['url'] ?? null,
        ];

        return [
            'id' => $this->id,
            'type' => 'database',
            'notification_type' => $this->type,
            'event_type' => $data['event_type'] ?? $this->type,
            'category' => $data['category'] ?? 'general',
            'severity' => $data['severity'] ?? 'info',
            'title' => $data['title'] ?? 'Notifikasi',
            'message' => $data['message'] ?? '',
            'subject' => $subject,
            'actor' => $data['actor'] ?? null,
            'action' => $action,
            'metadata' => $data['metadata'] ?? [],
            'data' => $data,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'is_read' => ! is_null($this->read_at),
        ];
    }
}
