<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value ?? $this->role,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'orders_count' => (int) ($this->orders_count ?? 0),
            'addresses_count' => (int) ($this->addresses_count ?? 0),
            'delivered_orders_count' => (int) ($this->delivered_orders_count ?? 0),
            'paid_total' => (int) ($this->paid_orders_sum_grand_total ?? 0),
            'latest_order_at' => $this->latest_order_at,
            'addresses' => $this->whenLoaded('addresses', fn () => $this->addresses->map(fn ($address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'name' => $address->name,
                'phone' => $address->phone,
                'district' => $address->district,
                'area' => $address->area,
                'postal_code' => $address->postal_code,
                'address_line' => $address->address_line,
                'is_default' => $address->is_default,
            ])->values()),
            'orders' => $this->whenLoaded('orders', fn () => $this->orders->map(fn ($order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status?->value ?? $order->status,
                'payment_status' => $order->payment_status?->value ?? $order->payment_status,
                'payment_method' => $order->payment_method?->value ?? $order->payment_method,
                'shipping_district' => $order->shipping_district,
                'shipping_area' => $order->shipping_area,
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at?->toISOString(),
            ])->values()),
        ];
    }
}
