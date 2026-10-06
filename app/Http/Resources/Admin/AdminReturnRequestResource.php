<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ShipmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminReturnRequestResource extends JsonResource
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
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'refunded_at' => $this->refunded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'customer' => $this->whenLoaded('user', fn (): ?array => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status?->value ?? $this->order->status,
                'payment_status' => $this->order->payment_status?->value ?? $this->order->payment_status,
                'payment_method' => $this->order->payment_method?->value ?? $this->order->payment_method,
                'customer_name' => $this->order->customer_name,
                'customer_phone' => $this->order->customer_phone,
                'shipping_district' => $this->order->shipping_district,
                'shipping_area' => $this->order->shipping_area,
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
