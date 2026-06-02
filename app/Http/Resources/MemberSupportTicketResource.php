<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_no' => $this->ticket_no,
            'category' => $this->category,
            'priority' => $this->priority,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
