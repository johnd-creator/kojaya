<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'points_required' => (int) $this->points_required,
            'stock' => (int) $this->stock,
            'valid_until' => $this->valid_until?->toDateString(),
            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
