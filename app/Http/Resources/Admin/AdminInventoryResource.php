<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInventoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $availableQuantity = max(0, (int) $this->stock_quantity - (int) $this->reserved_quantity);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'stock_quantity' => $this->stock_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $availableQuantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => (int) $this->stock_quantity <= (int) $this->low_stock_threshold,
            'oversell_allowed' => $this->oversell_allowed,
            'is_active' => $this->is_active,
            'adjustments_count' => $this->adjustments_count,
            'updated_at' => $this->updated_at?->toISOString(),
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'status' => $this->product->status?->value ?? $this->product->status,
                'category' => $this->product->relationLoaded('category') && $this->product->category ? [
                    'id' => $this->product->category->id,
                    'name' => $this->product->category->name,
                    'slug' => $this->product->category->slug,
                ] : null,
                'brand' => $this->product->relationLoaded('brand') && $this->product->brand ? [
                    'id' => $this->product->brand->id,
                    'name' => $this->product->brand->name,
                    'slug' => $this->product->brand->slug,
                    'is_official' => $this->product->brand->is_official,
                ] : null,
            ] : null),
        ];
    }
}
