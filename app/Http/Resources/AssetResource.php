<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'asset_code' => $this->asset_code,
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
