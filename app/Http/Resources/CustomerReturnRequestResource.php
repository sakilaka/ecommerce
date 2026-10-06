<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerReturnRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'status' => $this->status?->value ?? $this->status,
            'refund_amount' => $this->refund_amount,
            'reason' => $this->reason,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status?->value ?? $this->order->status,
                'grand_total' => $this->order->grand_total,
                'created_at' => $this->order->created_at?->toISOString(),
            ] : null),
            'pickup_shipment' => $this->whenLoaded('pickupShipment', fn (): ?ShipmentResource => $this->pickupShipment ? ShipmentResource::make($this->pickupShipment) : null),
            'order_item' => $this->whenLoaded('orderItem', fn (): ?array => $this->orderItem ? [
                'id' => $this->orderItem->id,
                'product_name' => $this->orderItem->product_name,
                'variant_name' => $this->orderItem->variant_name,
                'sku' => $this->orderItem->sku,
                'quantity' => $this->orderItem->quantity,
                'unit_price' => $this->orderItem->unit_price,
                'line_total' => $this->orderItem->line_total,
                'is_refundable' => $this->orderItem->is_refundable,
                'return_window_days' => $this->orderItem->return_window_days,
            ] : null),
        ];
    }
}
