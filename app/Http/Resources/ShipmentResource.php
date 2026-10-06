<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_request_id' => $this->return_request_id,
            'direction' => $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'courier_name' => $this->courier_name,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'delivery_charge' => $this->delivery_charge,
            'cod_charge' => $this->cod_charge,
            'cod_amount' => $this->cod_amount,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'recipient_district' => $this->recipient_district,
            'recipient_area' => $this->recipient_area,
            'recipient_address' => $this->recipient_address,
            'estimated_delivery_at' => $this->estimated_delivery_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'events' => ShipmentEventResource::collection($this->whenLoaded('events', fn () => $this->events->where('is_public', true)->values())),
        ];
    }
}
