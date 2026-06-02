<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'assigned_to' => $this->assigned_to,
            'asset' => $this->whenLoaded('asset'),
            'organization' => $this->whenLoaded('organization'),
            'checklists' => $this->whenLoaded('checklists'),
            'parts' => $this->whenLoaded('parts'),
            'attachments' => $this->whenLoaded('attachments'),
            'timelines' => $this->whenLoaded('timelines'),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'completion_notes' => $this->completion_notes,
        ];
    }
}
