<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');
        $subtotal = $this->relationLoaded('items')
            ? $this->items->sum(fn ($item): int => $item->unit_price * $item->quantity)
            : 0;
        $itemCount = $this->relationLoaded('items')
            ? $this->items->sum('quantity')
            : 0;

        return [
            'id' => $this->id,
            'cart_session_id' => $this->session_id,
            'status' => $this->status?->value ?? $this->status,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'expires_at' => $this->expires_at?->toISOString(),
            'items' => CartItemResource::collection($items),
            'summary' => [
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => $subtotal,
            ],
        ];
    }
}
