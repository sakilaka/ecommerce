<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'context' => $this->context,
            'created_at' => $this->created_at?->toISOString(),
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
                'price' => $this->product->price,
                'compare_at_price' => $this->product->compare_at_price,
                'status' => $this->product->status?->value ?? $this->product->status,
            ] : null),
            'variant' => $this->whenLoaded('productVariant', fn (): ?array => $this->productVariant ? [
                'id' => $this->productVariant->id,
                'name' => $this->productVariant->name,
                'sku' => $this->productVariant->sku,
                'stock_quantity' => $this->productVariant->stock_quantity,
            ] : null),
        ];
    }
}
