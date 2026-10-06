<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminShipmentResource extends JsonResource
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
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->toISOString(),
            'events' => AdminShipmentEventResource::collection($this->whenLoaded('events')),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status?->value ?? $this->order->status,
                'payment_status' => $this->order->payment_status?->value ?? $this->order->payment_status,
                'payment_method' => $this->order->payment_method?->value ?? $this->order->payment_method,
                'customer_name' => $this->order->customer_name,
                'customer_phone' => $this->order->customer_phone,
                'grand_total' => $this->order->grand_total,
                'items_count' => $this->order->items_count,
            ] : null),
        ];
    }
}
