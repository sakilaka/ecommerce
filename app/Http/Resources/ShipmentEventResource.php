<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value ?? $this->status,
            'event_type' => $this->event_type,
            'location' => $this->location,
            'message' => $this->message,
            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
